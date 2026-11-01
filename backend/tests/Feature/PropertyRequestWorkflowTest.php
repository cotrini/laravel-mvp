<?php

namespace Tests\Feature;

use App\Mail\AdminPropertyRequest;
use App\Modules\Property\Models\Property;
use App\Modules\Property\Models\PropertyRequest;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PropertyRequestWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_request_verification_and_administrators_are_notified(): void
    {
        $owner = User::factory()->create();
        $admin = $this->createAdmin();
        $property = $this->createProperty($owner);
        Mail::fake();

        $response = $this->actingAs($owner)->post(route('properties.requests.store', $property), [
            'type' => PropertyRequest::TYPE_VERIFICATION,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Tu solicitud fue enviada al administrador.');
        $this->assertDatabaseHas('property_requests', [
            'property_id' => $property->id,
            'user_id' => $owner->id,
            'type' => PropertyRequest::TYPE_VERIFICATION,
            'status' => PropertyRequest::STATUS_PENDING,
        ]);
        Mail::assertSent(AdminPropertyRequest::class, function (AdminPropertyRequest $mail) use ($admin, $property): bool {
            return $mail->hasTo($admin->email)
                && $mail->propertyRequest->property->is($property);
        });
    }

    public function test_owner_has_one_combined_request_for_missing_video_and_360_media(): void
    {
        $owner = User::factory()->create();
        $admin = $this->createAdmin();
        $property = $this->createProperty($owner);
        Mail::fake();

        $this->actingAs($owner)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('value="video_and_tour_360"', false)
            ->assertSee('Solicitar video y recorrido 360°');

        $response = $this->post(route('properties.requests.store', $property), [
            'type' => PropertyRequest::TYPE_VIDEO_AND_TOUR_360,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Tu solicitud fue enviada al administrador.');
        $this->assertDatabaseHas('property_requests', [
            'property_id' => $property->id,
            'user_id' => $owner->id,
            'type' => PropertyRequest::TYPE_VIDEO_AND_TOUR_360,
            'status' => PropertyRequest::STATUS_PENDING,
        ]);
        Mail::assertSent(AdminPropertyRequest::class, fn (AdminPropertyRequest $mail): bool => $mail->hasTo($admin->email)
            && $mail->propertyRequest->property->is($property)
        );
    }

    public function test_only_the_property_owner_can_create_owner_requests(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $this->actingAs($otherUser)
            ->post(route('properties.requests.store', $property), [
                'type' => PropertyRequest::TYPE_VIDEO,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('property_requests', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_admin_can_review_and_complete_property_requests(): void
    {
        $owner = User::factory()->create();
        $admin = $this->createAdmin();
        $property = $this->createProperty($owner);
        $propertyRequest = PropertyRequest::query()->create([
            'property_id' => $property->id,
            'user_id' => $owner->id,
            'type' => PropertyRequest::TYPE_TOUR_360,
            'status' => PropertyRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.property-requests.index'))
            ->assertOk()
            ->assertSee($property->title)
            ->assertSee('Recorrido 360°');

        $this->post(route('admin.property-requests.complete', $propertyRequest))
            ->assertRedirect();

        $this->assertDatabaseHas('property_requests', [
            'id' => $propertyRequest->id,
            'status' => PropertyRequest::STATUS_COMPLETED,
        ]);
    }

    public function test_non_admin_cannot_review_property_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.property-requests.index'))
            ->assertForbidden();
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
            'title' => 'Apartamento de solicitudes',
            'description' => 'Propiedad de prueba.',
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
