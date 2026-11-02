<?php

namespace Tests\Feature;

use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PropertyStatusTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_verify_a_property_and_the_badge_is_public(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Casa verificada de prueba',
        ]);
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('properties.verification.toggle', $property));

        $response->assertRedirect();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_verified' => true,
        ]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Propiedad verificada');
        $this->get(route('properties.index'))
            ->assertOk()
            ->assertSee('aria-label="Propiedad verificada"', false);
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-label="Propiedad verificada"', false);
    }

    public function test_non_admin_cannot_change_property_verification(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(route('properties.verification.toggle', $property));

        $response->assertForbidden();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_verified' => false,
        ]);
    }

    public function test_unverified_property_has_a_clear_badge_in_public_views(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Casa aún sin verificar',
        ]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Propiedad no verificada aún');
        $this->get(route('properties.index'))
            ->assertOk()
            ->assertSee('aria-label="Propiedad no verificada aún"', false)
            ->assertSee('Casa aún sin verificar');
    }

    public function test_owner_can_deactivate_and_reactivate_a_property_without_deleting_it(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('properties.active.toggle', $property))
            ->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_active' => false,
        ]);
        $this->assertModelExists($property);

        $this->post(route('properties.active.toggle', $property))
            ->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_active' => true,
        ]);
    }

    public function test_deactivating_a_property_updates_dashboard_status_and_hides_public_search_results(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Casa desactivada desde el panel',
            'city' => 'Manizales',
        ]);
        $this->actingAs($owner);

        $this->post(route('properties.active.toggle', $property))
            ->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_active' => false,
        ]);
        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Inactiva')
            ->assertSee('Activar propiedad')
            ->assertDontSee('Desactivar propiedad');
        $this->get(route('home', ['destination' => 'Manizales']))
            ->assertOk()
            ->assertDontSee('Casa desactivada desde el panel');
        $this->get(route('properties.index', ['destination' => 'Manizales']))
            ->assertOk()
            ->assertDontSee('Casa desactivada desde el panel');
    }

    public function test_deactivated_properties_are_excluded_from_public_catalogs_and_details(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Casa temporalmente desactivada',
        ]);
        $property->is_active = false;
        $property->save();

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Casa temporalmente desactivada');
        $this->get(route('properties.index', ['listing_type' => 'venta']))
            ->assertOk()
            ->assertDontSee('Casa temporalmente desactivada');
        $this->get(route('properties.show', $property))
            ->assertNotFound();
        $this->get(route('properties.blueprint.show', $property))
            ->assertNotFound();
        $this->post(route('properties.media-request', $property), ['media_type' => 'video'])
            ->assertNotFound();
    }

    public function test_filmmaker_cannot_toggle_a_property_status_even_when_assigned(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = User::factory()->create();
        $filmmaker->role = User::ROLE_FILMMAKER;
        $filmmaker->save();
        $filmmaker->assignedProperties()->attach($property);

        $response = $this->actingAs($filmmaker)->post(route('properties.active.toggle', $property));

        $response->assertForbidden();
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'is_active' => true,
        ]);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        return $admin;
    }
}
