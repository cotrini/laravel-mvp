<?php

namespace Tests\Feature;

use App\Modules\Help\Models\HelpArticle;
use App\Modules\Payment\Models\PaymentOrder;
use App\Modules\Payment\Services\WompiService;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\HabeasDataConsent;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentsHabeasDataHelpTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.wompi.events_secret' => 'events-secret',
            'services.wompi.integrity_secret' => 'integrity-secret',
            'services.wompi.public_key' => 'pub_test_123',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function wompiEvent(PaymentOrder $order, string $status, ?int $amount = null): array
    {
        $transaction = [
            'id' => 'tx-1',
            'reference' => $order->reference,
            'status' => $status,
            'amount_in_cents' => $amount ?? $order->amount_in_cents,
            'currency' => $order->currency,
        ];
        $timestamp = 1730000000;

        return [
            'event' => 'transaction.updated',
            'data' => ['transaction' => $transaction],
            'timestamp' => $timestamp,
            'signature' => [
                'properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'],
                'checksum' => hash('sha256', $transaction['id'].$transaction['status'].$transaction['amount_in_cents'].$timestamp.'events-secret'),
            ],
        ];
    }

    private function box(string $type, string $content): string
    {
        return pack('N', strlen($content) + 8).$type.$content;
    }

    private function fakeMp4(int $width, int $height, int $seconds = 10): UploadedFile
    {
        $mvhd = $this->box('mvhd', pack('N', 0).pack('N', 0).pack('N', 0).pack('N', 1000).pack('N', $seconds * 1000).str_repeat("\0", 80));
        $tkhd = $this->box('tkhd', pack('N', 0).str_repeat("\0", 72).pack('N', $width << 16).pack('N', $height << 16));
        $moov = $this->box('moov', $mvhd.$this->box('trak', $tkhd));
        $content = $this->box('ftyp', 'isom'.pack('N', 512).'isom').$moov;
        $path = tempnam(sys_get_temp_dir(), 'mp4');
        file_put_contents($path, $content);

        return new UploadedFile($path, 'video.mp4', 'video/mp4', null, true);
    }

    public function test_regular_user_can_upload_full_hd_video_but_not_larger_or_longer(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $route = route('properties.video.store', $property);

        $this->actingAs($owner)->post($route, ['video' => $this->fakeMp4(3840, 2160)])
            ->assertSessionHasErrors('video');
        $this->actingAs($owner)->post($route, ['video' => $this->fakeMp4(1920, 1080, 90)])
            ->assertSessionHasErrors('video');
        $this->assertEmpty(Storage::disk('r2')->allFiles());

        $this->actingAs($owner)->post($route, ['video' => $this->fakeMp4(1920, 1080)])
            ->assertSessionHasNoErrors();
        $this->assertNotEmpty(Storage::disk('r2')->allFiles());
    }

    public function test_second_property_redirects_to_payment_and_paid_order_unlocks_it(): void
    {
        $user = User::factory()->create();
        Property::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('properties.create'))
            ->assertRedirect(route('payments.publication.show'));

        PaymentOrder::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('properties.create'))->assertOk();
    }

    public function test_checkout_creates_pending_order_and_redirects_with_integrity_signature(): void
    {
        $user = User::factory()->create();
        Property::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('payments.publication.store'));

        $order = PaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(PaymentOrder::STATUS_PENDING, $order->status);
        $this->assertSame(5000000, $order->amount_in_cents);

        $expected = hash('sha256', $order->reference.'5000000COP'.'integrity-secret');
        $response->assertRedirectContains('signature%3Aintegrity='.$expected);
    }

    public function test_webhook_approves_order_with_valid_signature(): void
    {
        $order = PaymentOrder::factory()->create();

        $this->postJson(route('webhooks.wompi'), $this->wompiEvent($order, 'APPROVED'))->assertOk();

        $order->refresh();
        $this->assertSame(PaymentOrder::STATUS_APPROVED, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $order = PaymentOrder::factory()->create();
        $event = $this->wompiEvent($order, 'APPROVED');
        $event['signature']['checksum'] = 'forged';

        $this->postJson(route('webhooks.wompi'), $event)->assertForbidden();

        $this->assertSame(PaymentOrder::STATUS_PENDING, $order->refresh()->status);
    }

    public function test_webhook_ignores_amount_mismatch_and_never_downgrades_approved_orders(): void
    {
        $pending = PaymentOrder::factory()->create();
        $this->postJson(route('webhooks.wompi'), $this->wompiEvent($pending, 'APPROVED', 100))->assertOk();
        $this->assertSame(PaymentOrder::STATUS_PENDING, $pending->refresh()->status);

        $approved = PaymentOrder::factory()->approved()->create();
        $this->postJson(route('webhooks.wompi'), $this->wompiEvent($approved, 'DECLINED'))->assertOk();
        $this->assertSame(PaymentOrder::STATUS_APPROVED, $approved->refresh()->status);
    }

    public function test_wompi_service_maps_statuses(): void
    {
        $service = app(WompiService::class);

        $this->assertSame(PaymentOrder::STATUS_DECLINED, $service->mapStatus('VOIDED'));
        $this->assertSame(PaymentOrder::STATUS_PENDING, $service->mapStatus('PENDING'));
    }

    public function test_habeas_data_blocks_writes_until_accepted_and_stores_trace(): void
    {
        $user = User::factory()->withoutHabeasDataConsent()->create();

        $this->actingAs($user)->get(route('help.index'))->assertOk()->assertSee('Aceptar y Continuar');

        $this->actingAs($user)->post(route('payments.publication.store'))
            ->assertSessionHasErrors('habeas_data');
        $this->assertDatabaseCount('payment_orders', 0);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->post(route('habeas-data.accept'))
            ->assertRedirect();

        $consent = HabeasDataConsent::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('203.0.113.9', $consent->ip_address);
        $this->assertNotNull($user->refresh()->habeas_data_accepted_at);
    }

    public function test_help_center_lists_articles_with_r2_video_and_restricts_creation_to_admins(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('HelpCenter/demo.mp4', 'video');
        HelpArticle::factory()->create(['title' => 'Cómo publicar', 'video_path' => 'HelpCenter/demo.mp4']);
        HelpArticle::factory()->create(['title' => 'Borrador oculto', 'is_published' => false]);

        $this->get(route('help.index'))
            ->assertOk()
            ->assertSee('Cómo publicar')
            ->assertSee('<video', false)
            ->assertDontSee('Borrador oculto');

        $user = User::factory()->create();
        $this->actingAs($user)->post(route('help.store'), [])->assertForbidden();
    }
}
