<?php

namespace Tests\Feature;

use App\Modules\Property\Models\City;
use App\Modules\Property\Models\Department;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyImageManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_and_edit_forms_render_the_photo_dropzone_without_tour_url_fields(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);

        $this->actingAs($owner)
            ->get(route('properties.create'))
            ->assertOk()
            ->assertSee('Arrastra tus fotos aquí o selecciona archivos')
            ->assertSee('data-image-dropzone', false)
            ->assertDontSee('name="tour_360_url"', false)
            ->assertDontSee('name="tour_3d_url"', false);

        $this->get(route('properties.edit', $property))
            ->assertOk()
            ->assertSee('Administrar fotografías')
            ->assertSee('Arrastra tus fotos aquí o selecciona archivos')
            ->assertDontSee('name="tour_360_url"', false)
            ->assertDontSee('name="tour_3d_url"', false);
    }

    public function test_owner_can_publish_a_property_with_photos_from_the_creation_form(): void
    {
        Storage::fake('r2');
        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        $city = City::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Manizales',
        ]);
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->post(route('properties.store'), [
            'title' => 'Apartamento con fotografías',
            'description' => 'Descripción del inmueble.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'listing_type' => 'venta',
            'department_id' => $department->id,
            'city_id' => $city->id,
            'neighborhood' => 'Chipre',
            'is_furnished' => '0',
            'has_elevator' => '0',
            'images' => [
                UploadedFile::fake()->image('fachada.jpg'),
                UploadedFile::fake()->image('sala.png'),
            ],
        ]);

        $property = Property::query()->where('title', 'Apartamento con fotografías')->firstOrFail();
        $response->assertRedirect(route('properties.show', $property));
        $this->assertDatabaseCount('media', 2);

        foreach ($property->media()->where('type', 'image')->get() as $image) {
            Storage::disk('r2')->assertExists($image->url_or_path);
        }
    }

    public function test_owner_can_add_photos_to_an_existing_property_without_replacing_existing_photos(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $existingPath = "Pictures/{$property->id}/existing.jpg";
        Storage::disk('r2')->put($existingPath, 'existing photo');
        $property->media()->create([
            'type' => 'image',
            'url_or_path' => $existingPath,
            'title' => 'existing.jpg',
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
        ]);

        $this->actingAs($owner)
            ->from(route('properties.edit', $property))
            ->post(route('properties.images.store', $property), [
                'images' => [
                    UploadedFile::fake()->image('living-room.jpg'),
                    UploadedFile::fake()->image('kitchen.png'),
                ],
            ])
            ->assertRedirect(route('properties.edit', $property))
            ->assertSessionHas('success', 'Las fotografías se agregaron correctamente.');

        $this->assertDatabaseCount('media', 3);
        $this->assertDatabaseHas('media', [
            'property_id' => $property->id,
            'url_or_path' => $existingPath,
        ]);
        foreach ($property->media()->where('type', 'image')->get() as $image) {
            Storage::disk('r2')->assertExists($image->url_or_path);
        }
    }

    public function test_owner_can_delete_only_an_image_belonging_to_their_property(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $imagePath = "Pictures/{$property->id}/to-delete.jpg";
        $otherImagePath = "Pictures/{$property->id}/keep.jpg";
        Storage::disk('r2')->put($imagePath, 'image to delete');
        Storage::disk('r2')->put($otherImagePath, 'image to keep');
        $imageToDelete = $property->media()->create([
            'type' => 'image',
            'url_or_path' => $imagePath,
            'title' => 'to-delete.jpg',
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
        ]);
        $imageToKeep = $property->media()->create([
            'type' => 'image',
            'url_or_path' => $otherImagePath,
            'title' => 'keep.jpg',
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
        ]);
        $otherProperty = $this->createProperty(User::factory()->create());
        $foreignImagePath = "Pictures/{$otherProperty->id}/foreign.jpg";
        Storage::disk('r2')->put($foreignImagePath, 'foreign image');
        $foreignImage = $otherProperty->media()->create([
            'type' => 'image',
            'url_or_path' => $foreignImagePath,
            'title' => 'foreign.jpg',
            'mediable_type' => Property::class,
            'mediable_id' => $otherProperty->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('properties.images.destroy', [$property, $foreignImage]))
            ->assertNotFound();

        $this->assertModelExists($foreignImage);
        Storage::disk('r2')->assertExists($foreignImagePath);

        $this->actingAs($owner)
            ->delete(route('properties.images.destroy', [$property, $imageToDelete]))
            ->assertRedirect();

        $this->assertModelMissing($imageToDelete);
        $this->assertModelExists($imageToKeep);
        Storage::disk('r2')->assertMissing($imagePath);
        Storage::disk('r2')->assertExists($otherImagePath);
    }

    public function test_photo_upload_rejects_non_owners_and_caps_total_photos_at_twenty(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($otherUser)
            ->post(route('properties.images.store', $property), [
                'images' => [UploadedFile::fake()->image('unauthorized.jpg')],
            ])
            ->assertForbidden();

        $this->actingAs($owner);
        foreach (range(1, 20) as $index) {
            $property->media()->create([
                'type' => 'image',
                'url_or_path' => "Pictures/{$property->id}/photo-{$index}.jpg",
                'title' => "photo-{$index}.jpg",
                'mediable_type' => Property::class,
                'mediable_id' => $property->id,
            ]);
        }

        $this->from(route('properties.edit', $property))
            ->post(route('properties.images.store', $property), [
                'images' => [UploadedFile::fake()->image('extra.jpg')],
            ])
            ->assertRedirect(route('properties.edit', $property))
            ->assertSessionHasErrors([
                'images' => 'Cada inmueble puede tener máximo 20 fotografías. Elimina alguna antes de agregar más.',
            ]);

        $this->assertDatabaseCount('media', 20);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    private function createProperty(User $owner): Property
    {
        return $owner->properties()->create([
            'title' => 'Apartamento para fotos',
            'description' => 'Inmueble de prueba para administrar fotografías.',
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
