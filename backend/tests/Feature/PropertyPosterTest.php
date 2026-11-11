<?php

namespace Tests\Feature;

use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

class PropertyPosterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_open_the_printable_poster_of_their_property(): void
    {
        $owner = User::factory()->create(['phone_number' => '+573113611426']);
        $property = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Casa con cartel',
            'listing_type' => 'venta',
        ]);

        $this->actingAs($owner)
            ->get(route('propiedades.cartel', $property->id))
            ->assertOk()
            ->assertSee('SE VENDE')
            ->assertSee('3113611426')
            ->assertSee('site-header', false)
            ->assertSee(Js::from(route('properties.show', $property->id))->toHtml(), false);
    }

    public function test_another_user_cannot_open_the_poster(): void
    {
        $property = Property::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs(User::factory()->create())
            ->get(route('propiedades.cartel', $property->id))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $property = Property::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->get(route('propiedades.cartel', $property->id))
            ->assertRedirect();
    }

    public function test_dashboard_groups_secondary_actions_in_a_menu_with_the_poster_shortcut(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(route('propiedades.cartel', $property->id), false)
            ->assertSee('Aviso imprimible')
            ->assertSee('data-actions-menu', false)
            ->assertSee('Solicitar avalúo comercial')
            ->assertSee('Eliminar propiedad');
    }
}
