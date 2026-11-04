<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateFirebaseToken;
use App\Modules\Property\Models\Property;
use App\Modules\Tours360\Models\Tour;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_core_health_endpoint_returns_the_api_version(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('version', 'v1');
    }

    public function test_property_catalog_only_returns_active_public_properties(): void
    {
        $owner = User::factory()->create();
        $publicProperty = Property::factory()->for($owner)->create();
        Property::factory()->for($owner)->create(['is_active' => false]);

        $this->getJson('/api/v1/properties')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $publicProperty->id)
            ->assertJsonPath('data.0.identification_code', $publicProperty->identification_code);
    }

    public function test_protected_mobile_endpoints_return_401_without_a_firebase_token(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Se requiere un Firebase ID token válido.');
    }

    public function test_firebase_authentication_returns_401_without_a_bearer_token(): void
    {
        $this->postJson('/api/v1/auth/firebase')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Envía el Firebase ID token en el encabezado Bearer.');
    }

    public function test_credit_simulation_api_returns_the_amortization_results(): void
    {
        $this->postJson('/api/v1/credit/simulations', [
            'property_value' => 15000000,
            'initial_payment_type' => 'percentage',
            'initial_payment_percentage' => 20,
            'annual_effective_rate' => 0,
            'term_unit' => 'months',
            'term_value' => 12,
        ])
            ->assertOk()
            ->assertJsonPath('data.loan_amount', 12000000)
            ->assertJsonPath('data.simulation.monthly_payment', 1000000)
            ->assertJsonPath('data.term_months', 12);
    }

    public function test_mobile_client_can_create_a_tour_with_uploaded_scene_urls(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $property = Property::factory()->create();
        $payload = [
            'property_id' => $property->id,
            'title' => 'Recorrido móvil',
            'description' => 'Recorrido creado desde Expo.',
            'is_published' => true,
            'scenes' => [
                [
                    'title' => 'Sala',
                    'caption' => 'Vista principal',
                    'image_url' => 'https://media.example.test/sala.jpg',
                    'sort_order' => 0,
                ],
            ],
        ];

        $response = $this->withoutMiddleware(AuthenticateFirebaseToken::class)
            ->actingAs($admin)
            ->postJson('/api/v1/tours', $payload);

        $tour = Tour::query()->firstOrFail();
        $response->assertCreated()
            ->assertJsonPath('data.title', 'Recorrido móvil')
            ->assertJsonPath('data.scenes.0.title', 'Sala');
        $this->assertDatabaseHas('scenes', [
            'tour_id' => $tour->id,
            'title' => 'Sala',
            'image_url' => 'https://media.example.test/sala.jpg',
        ]);
        $this->assertSame($tour->id, $property->refresh()->tour_id);
    }

    public function test_mobile_client_can_upload_a_valid_property_video(): void
    {
        Storage::fake('r2');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $property = Property::factory()->create();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'mobile-video-');
        file_put_contents($temporaryPath, $this->validMp4Content());

        try {
            $video = new UploadedFile(
                $temporaryPath,
                'property.mp4',
                'video/mp4',
                UPLOAD_ERR_OK,
                true,
            );

            $response = $this->withoutMiddleware(AuthenticateFirebaseToken::class)
                ->actingAs($admin)
                ->withHeader('Accept', 'application/json')
                ->post("/api/v1/properties/{$property->id}/video", ['video' => $video]);
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $response->assertCreated()
            ->assertJsonPath('data.type', 'video')
            ->assertJsonPath('data.title', 'property.mp4');
        $this->assertDatabaseHas('media', [
            'property_id' => $property->id,
            'type' => 'video',
            'title' => 'property.mp4',
        ]);
        $storedMedia = $property->media()->where('type', 'video')->firstOrFail();
        Storage::disk('r2')->assertExists($storedMedia->url_or_path);
    }

    private function validMp4Content(): string
    {
        $fileTypeBox = pack('N', 16).'ftyp'.'isom'.pack('N', 0).'isom';
        $movieHeader = "\0\0\0\0".pack('N4', 0, 0, 1000, 30000);
        $movieHeaderBox = pack('N', strlen($movieHeader) + 8).'mvhd'.$movieHeader;
        $movieBox = pack('N', strlen($movieHeaderBox) + 8).'moov'.$movieHeaderBox;

        return $fileTypeBox.$movieBox;
    }
}
