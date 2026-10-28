<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Property\Models\Property;
use App\Modules\Tours360\Models\Tour;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Tour360ManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_tour_form_disables_adding_scenes_until_the_current_image_is_ready(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->get(route('tours360.create', ['property_id' => $property->id]));

        $response->assertOk()
            ->assertSee('id="tour-add-scene" disabled', false)
            ->assertSee('maxImageBytes = 15728640', false)
            ->assertSee('solo podrás agregar la siguiente escena cuando la foto se haya cargado correctamente');
    }

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

    public function test_deleting_a_tour_removes_its_r2_images_but_keeps_other_property_files(): void
    {
        Storage::fake('r2');
        config([
            'filesystems.disks.r2.endpoint' => 'https://account.r2.cloudflarestorage.com',
            'filesystems.disks.r2.bucket' => 'colombiasincomision',
            'filesystems.disks.r2.url' => 'https://media.example.com/storage',
        ]);
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $tour = Tour::factory()->create([
            'cover_url' => "https://media.example.com/storage/Tours360/{$property->id}/sala.jpg",
        ]);
        $property->tour()->associate($tour)->save();
        $scenePath = "Tours360/{$property->id}/sala.jpg";
        $otherPath = "Pictures/{$property->id}/fachada.jpg";
        Storage::disk('r2')->put($scenePath, 'scene');
        Storage::disk('r2')->put($otherPath, 'property image');
        $tour->scenes()->create([
            'title' => 'Sala',
            'image_url' => "https://media.example.com/storage/{$scenePath}",
            'sort_order' => 0,
        ]);
        $this->actingAs($owner);

        $response = $this->delete(route('tours360.destroy', $tour->id));

        $response->assertRedirect(route('dashboard.index'));
        $this->assertDatabaseMissing('tours', ['id' => $tour->id]);
        $this->assertDatabaseHas('properties', ['id' => $property->id, 'tour_id' => null]);
        Storage::disk('r2')->assertMissing($scenePath);
        Storage::disk('r2')->assertExists($otherPath);
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

    public function test_property_owner_can_upload_a_tour_scene_image_to_r2(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);
        $image = $this->panoramaImageUpload();
        $originalImage = $image->get();

        $response = $this->post(route('tours360.store'), [
            'property_id' => $property->id,
            'title' => 'Recorrido del apartamento',
            'scenes' => [
                [
                    'title' => 'Sala',
                    'image' => $image,
                ],
            ],
        ]);

        $tour = Tour::query()->firstOrFail();
        $scene = $tour->scenes()->firstOrFail();
        $storedImages = Storage::disk('r2')->files("Tours360/{$property->id}");
        $response->assertRedirect(route('dashboard.index'));
        $this->assertCount(1, $storedImages);
        Storage::disk('r2')->assertExists($storedImages[0]);
        $this->assertStringEndsWith('.png', $storedImages[0]);
        $this->assertSame($originalImage, Storage::disk('r2')->get($storedImages[0]));
        $this->assertSame(Storage::disk('r2')->url($storedImages[0]), $scene->image_url);
        $this->assertSame($scene->image_url, $tour->cover_url);
    }

    public function test_scene_photos_are_uploaded_individually_then_linked_when_the_tour_is_saved(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);
        $stagedPaths = [];
        $originalImages = [];

        for ($index = 1; $index <= 5; $index++) {
            $image = $this->panoramaImageUpload();
            $originalImages[] = $image->get();
            $uploadResponse = $this->post(route('tours360.scene-images.store'), [
                'property_id' => $property->id,
                'image' => $image,
            ], ['Accept' => 'application/json']);
            $uploadResponse->assertOk();
            $stagedPaths[] = $uploadResponse->json('path');
        }

        foreach ($stagedPaths as $stagedPath) {
            Storage::disk('r2')->assertExists($stagedPath);
            $this->assertStringStartsWith("Tours360/{$property->id}/", $stagedPath);
            $this->assertStringNotContainsString('/pending/', $stagedPath);
        }

        $saveResponse = $this->post(route('tours360.store'), [
            'property_id' => $property->id,
            'title' => 'Recorrido del apartamento',
            'scenes' => array_map(
                fn (string $stagedPath, int $index): array => [
                    'title' => "Escena {$index}",
                    'staged_image' => $stagedPath,
                ],
                $stagedPaths,
                range(1, count($stagedPaths)),
            ),
        ]);

        $tour = Tour::query()->firstOrFail();
        $scenes = $tour->scenes()->get();
        $savedPaths = Storage::disk('r2')->files("Tours360/{$property->id}");
        $saveResponse->assertRedirect(route('dashboard.index'));
        $this->assertCount(5, $scenes);
        $this->assertCount(5, $savedPaths);

        foreach ($scenes as $index => $scene) {
            $this->assertStringNotContainsString('/pending/', $scene->image_url);
            $this->assertSame(Storage::disk('r2')->url($stagedPaths[$index]), $scene->image_url);
            $this->assertSame($originalImages[$index], Storage::disk('r2')->get($stagedPaths[$index]));
        }
    }

    public function test_scene_image_upload_rejects_photos_over_fifteen_megabytes(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->postJson(route('tours360.scene-images.store'), [
            'property_id' => $property->id,
            'image' => UploadedFile::fake()->image('panorama.jpg')->size(15361),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.image.0', 'La fotografía es muy pesada. El tamaño máximo permitido es de 15 MB.');
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_tour_rejects_more_than_thirty_scenes(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);
        $scenes = array_map(
            fn (int $index): array => [
                'title' => "Escena {$index}",
                'image_url' => "https://example.com/escena-{$index}.jpg",
            ],
            range(1, 31),
        );

        $response = $this->from(route('tours360.create', ['property_id' => $property->id]))
            ->post(route('tours360.store'), [
                'property_id' => $property->id,
                'title' => 'Recorrido con demasiadas escenas',
                'scenes' => $scenes,
            ]);

        $response->assertRedirect(route('tours360.create', ['property_id' => $property->id]))
            ->assertSessionHasErrors([
                'scenes' => 'Un recorrido 360° puede tener como máximo 30 escenas.',
            ]);
        $this->assertDatabaseCount('tours', 0);
    }

    public function test_tour_accepts_exactly_thirty_scenes(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);
        $scenes = array_map(
            fn (int $index): array => [
                'title' => "Escena {$index}",
                'image_url' => "https://example.com/escena-{$index}.jpg",
            ],
            range(1, 30),
        );

        $response = $this->post(route('tours360.store'), [
            'property_id' => $property->id,
            'title' => 'Recorrido con treinta escenas',
            'scenes' => $scenes,
        ]);

        $tour = Tour::query()->firstOrFail();
        $response->assertRedirect(route('dashboard.index'));
        $this->assertDatabaseCount('scenes', 30);
        $this->assertModelExists($tour);
    }

    public function test_oversized_tour_scene_image_returns_a_friendly_validation_error(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->from(route('tours360.create', ['property_id' => $property->id]))
            ->post(route('tours360.store'), [
                'property_id' => $property->id,
                'title' => 'Recorrido del apartamento',
                'scenes' => [
                    [
                        'title' => 'Sala',
                        'image' => UploadedFile::fake()->image('sala-360.jpg')->size(15361),
                    ],
                ],
            ]);

        $response->assertRedirect(route('tours360.create', ['property_id' => $property->id]))
            ->assertSessionHasErrors([
                'scenes.0.image' => 'La fotografía es muy pesada. El tamaño máximo permitido es de 15 MB.',
            ]);
        $this->assertDatabaseCount('tours', 0);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_property_owner_can_replace_a_tour_scene_image_with_an_r2_upload(): void
    {
        Storage::fake('r2');
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
            'title' => $tour->title,
            'scenes' => [
                [
                    'id' => $scene->id,
                    'title' => 'Sala',
                    'image_url' => $scene->image_url,
                    'image' => UploadedFile::fake()->image('sala-actualizada-360.jpg'),
                ],
            ],
        ]);

        $scene->refresh();
        $storedImages = Storage::disk('r2')->files("Tours360/{$property->id}");
        $response->assertRedirect(route('dashboard.index'));
        $this->assertCount(1, $storedImages);
        Storage::disk('r2')->assertExists($storedImages[0]);
        $this->assertSame(Storage::disk('r2')->url($storedImages[0]), $scene->image_url);
    }

    public function test_tour_scene_requires_a_url_or_uploaded_image(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->from(route('tours360.create', ['property_id' => $property->id]))
            ->post(route('tours360.store'), [
                'property_id' => $property->id,
                'title' => 'Recorrido incompleto',
                'scenes' => [
                    [
                        'title' => 'Sala',
                    ],
                ],
            ]);

        $response->assertRedirect(route('tours360.create', ['property_id' => $property->id]))
            ->assertSessionHasErrors('scenes.0.image_url');
        $this->assertDatabaseCount('tours', 0);
    }

    private function createProperty(User $owner): Property
    {
        $property = new Property([
            'title' => 'Apartamento de prueba',
            'description' => 'Inmueble de prueba para recorridos.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => 'Chipre',
        ]);
        $property->user()->associate($owner);
        $property->save();

        return $property;
    }

    private function panoramaImageUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'panorama.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/Y5sAAAAASUVORK5CYII='),
        );
    }
}
