<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Property\Models\Property;
use App\Modules\Tours360\Models\Tour;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class Tour360ManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_property_owner_can_create_a_published_tour_with_scenes(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->post(route('tours360.store'), [
            'property_id' => $property->id,
            'title' => 'Recorrido del apartamento',
            'description' => 'Visita los espacios principales.',
            'is_published' => '1',
            'scenes' => [
                [
                    'title' => 'Sala',
                    'image_url' => 'https://example.com/sala-360.jpg',
                    'sort_order' => '0',
                ],
            ],
        ]);

        $tour = Tour::query()->firstOrFail();
        $response->assertRedirect(route('dashboard.index'));
        $this->assertModelExists($tour);
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'tour_id' => $tour->id,
        ]);
        $this->assertDatabaseHas('scenes', [
            'tour_id' => $tour->id,
            'title' => 'Sala',
            'image_url' => 'https://example.com/sala-360.jpg',
        ]);
    }

    public function test_property_owner_can_update_tour_details_and_scenes(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $tour = Tour::factory()->create();
        $property->tour()->associate($tour)->save();
        $scene = $tour->scenes()->create([
            'title' => 'Sala',
            'image_url' => 'https://example.com/sala-360.jpg',
            'sort_order' => 0,
        ]);
        $this->actingAs($owner);

        $response = $this->put(route('tours360.update', $tour->id), [
            'title' => 'Recorrido actualizado',
            'description' => 'Descripción actualizada.',
            'is_published' => '1',
            'scenes' => [
                [
                    'id' => $scene->id,
                    'title' => 'Sala principal',
                    'image_url' => 'https://example.com/sala-principal-360.jpg',
                    'sort_order' => '0',
                ],
                [
                    'title' => 'Habitación',
                    'image_url' => 'https://example.com/habitacion-360.jpg',
                    'sort_order' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertDatabaseHas('tours', [
            'id' => $tour->id,
            'title' => 'Recorrido actualizado',
            'is_published' => true,
        ]);
        $this->assertDatabaseHas('scenes', [
            'id' => $scene->id,
            'title' => 'Sala principal',
            'image_url' => 'https://example.com/sala-principal-360.jpg',
        ]);
        $this->assertDatabaseHas('scenes', [
            'tour_id' => $tour->id,
            'title' => 'Habitación',
        ]);
    }

    public function test_user_cannot_create_a_tour_for_another_users_property(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($otherUser);

        $response = $this->post(route('tours360.store'), [
            'property_id' => $property->id,
            'title' => 'Recorrido no autorizado',
            'scenes' => [
                [
                    'title' => 'Sala',
                    'image_url' => 'https://example.com/sala-360.jpg',
                ],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('tours', 0);
    }

    public function test_tour_scene_images_must_use_https_urls(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->from(route('tours360.create', ['property_id' => $property->id]))
            ->post(route('tours360.store'), [
                'property_id' => $property->id,
                'title' => 'Recorrido de prueba',
                'scenes' => [
                    [
                        'title' => 'Sala',
                        'image_url' => 'http://example.com/sala-360.jpg',
                    ],
                ],
            ]);

        $response->assertRedirect(route('tours360.create', ['property_id' => $property->id]))
            ->assertSessionHasErrors('scenes.0.image_url');
        $this->assertDatabaseCount('tours', 0);
    }

    private function createProperty(User $owner): Property
    {
        return Property::query()->create([
            'user_id' => $owner->id,
            'title' => 'Apartamento de prueba',
            'description' => 'Inmueble de prueba para recorridos.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => 'Chipre',
        ]);
    }
}
