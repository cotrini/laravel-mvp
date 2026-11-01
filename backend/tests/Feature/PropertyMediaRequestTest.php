<?php

namespace Tests\Feature;

use App\Mail\AdminPropertyRequest;
use App\Mail\OwnerMediaRequest;
use App\Modules\Property\Models\Property;
use App\Modules\Property\Models\PropertyRequest;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PropertyMediaRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_a_visitors_360_request_to_the_owner_without_notifying_administrators(): void
    {
        $this->createAdmin();
        $owner = User::factory()->create(['phone_number' => '+573001234567']);
        $property = $this->createProperty($owner);
        Mail::fake();

        $response = $this->postJson(route('properties.media-request', $property), [
            'media_type' => PropertyRequest::TYPE_TOUR_360,
            'requester_name' => 'Visitante interesado',
            'requester_email' => 'visitor@example.test',
            'requester_phone' => '+573009876543',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'La solicitud se envió al propietario por correo electrónico.');
        $this->assertStringStartsWith(
            'https://wa.me/573001234567?text=',
            $response->json('whatsapp_url'),
        );
        $this->assertDatabaseCount('property_requests', 0);
        Mail::assertSent(OwnerMediaRequest::class, fn (OwnerMediaRequest $mail): bool => $mail->hasTo($owner->email)
            && $mail->property->is($property)
            && $mail->requesterName === 'Visitante interesado'
            && str_contains(strip_tags($mail->render()), 'un 70% más de probabilidades de ser vendidas en plazos menores')
        );
        Mail::assertNotSent(AdminPropertyRequest::class);
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

    public function test_rejects_owner_media_requests_without_a_requester_name(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $response = $this->postJson(route('properties.media-request', $property), [
            'media_type' => PropertyRequest::TYPE_VIDEO,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('requester_name');
        Mail::assertNothingOutgoing();
    }

    public function test_notifies_administrators_for_architectural_plan_requests(): void
    {
        $this->createAdmin();
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $response = $this->postJson(route('properties.media-request', $property), [
            'media_type' => PropertyRequest::TYPE_BLUEPRINT,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'La solicitud fue enviada al administrador.']);
        Mail::assertSent(AdminPropertyRequest::class, fn (AdminPropertyRequest $mail): bool => $mail->propertyRequest->property->is($property)
        );
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        return $admin;
    }

    private function createProperty(User $owner): Property
    {
        return $owner->properties()->create([
            'title' => 'Apartamento de prueba',
            'description' => 'Inmueble de prueba para solicitudes de medios.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'listing_type' => 'venta',
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => 'Chipre',
        ]);
    }
}
