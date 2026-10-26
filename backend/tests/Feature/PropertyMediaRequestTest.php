<?php

namespace Tests\Feature;

use App\Mail\PropertyMediaRequest;
use App\Models\User;
use App\Modules\Property\Models\Property;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PropertyMediaRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_the_property_owner_an_email_for_a_media_request(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $response = $this->postJson(route('properties.media-request', $property), [
            'media_type' => 'tour_360',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Tu solicitud fue enviada al propietario.']);
        Mail::assertSent(PropertyMediaRequest::class, function (PropertyMediaRequest $mail) use ($owner, $property): bool {
            return $mail->hasTo($owner->email)
                && $mail->property->is($property)
                && $mail->mediaLabel === 'un recorrido virtual 360°';
        });
    }

    public function test_rejects_an_unknown_media_request_without_sending_email(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $response = $this->postJson(route('properties.media-request', $property), [
            'media_type' => 'email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('media_type');
        Mail::assertNothingOutgoing();
    }

    public function test_sends_the_property_owner_an_email_for_architectural_plans(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $response = $this->postJson(route('properties.media-request', $property), [
            'media_type' => 'blueprint',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Tu solicitud fue enviada al propietario.']);
        Mail::assertSent(PropertyMediaRequest::class, function (PropertyMediaRequest $mail) use ($owner, $property): bool {
            return $mail->hasTo($owner->email)
                && $mail->property->is($property)
                && $mail->mediaLabel === 'los planos arquitectónicos en PDF';
        });
    }

    private function createProperty(User $owner): Property
    {
        return Property::query()->create([
            'user_id' => $owner->id,
            'title' => 'Apartamento de prueba',
            'description' => 'Inmueble de prueba para solicitudes de medios.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => 'Chipre',
        ]);
    }
}
