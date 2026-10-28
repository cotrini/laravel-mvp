<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\City;
use App\Modules\Property\Models\Department;
use App\Modules\Property\Models\Property;
use App\Modules\Property\Support\Mp4DurationValidator;
use App\Modules\Tours360\Models\Tour;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyMediaStorageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_can_upload_property_photos_to_r2_when_editing(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $department = Department::query()->where('name', 'Caldas')->firstOrFail();
        $city = City::query()->where('name', 'Manizales')->firstOrFail();
        $this->actingAs($owner);

        $response = $this->put(route('properties.update', $property), [
            'title' => $property->title,
            'description' => $property->description,
            'price' => $property->price,
            'property_type' => $property->property_type,
            'listing_type' => 'venta',
            'department_id' => $department->id,
            'city_id' => $city->id,
            'neighborhood' => $property->neighborhood,
            'is_furnished' => '0',
            'has_elevator' => '0',
            'images' => [UploadedFile::fake()->image('fachada.jpg')],
        ]);

        $media = $property->media()->where('type', 'image')->firstOrFail();
        $response->assertRedirect(route('properties.show', $property->id));
        $this->assertModelExists($media);
        $this->assertStringStartsWith("Pictures/{$property->id}/", $media->url_or_path);
        Storage::disk('r2')->assertExists($media->url_or_path);

        $this->get(route('properties.show', $property->id))
            ->assertOk()
            ->assertSee($media->public_url, false);
    }

    public function test_home_page_uses_a_signed_url_for_property_photos_in_r2(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->buildTemporaryUrlsUsing(
            fn (string $path): string => "https://signed.example/{$path}",
        );
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $path = "Pictures/{$property->id}/fachada.jpg";
        Storage::disk('r2')->put($path, 'photo');
        $property->media()->create([
            'type' => 'image',
            'url_or_path' => $path,
            'title' => 'fachada.jpg',
            'is_primary' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee("https://signed.example/{$path}", false);
    }

    public function test_deleting_a_property_also_removes_its_tour_and_r2_images(): void
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
        $propertyImagePath = "Pictures/{$property->id}/fachada.jpg";
        $unrelatedTourPath = 'Tours360/999/sala.jpg';
        Storage::disk('r2')->put($scenePath, 'scene');
        Storage::disk('r2')->put($propertyImagePath, 'property image');
        Storage::disk('r2')->put($unrelatedTourPath, 'unrelated scene');
        $tour->scenes()->create([
            'title' => 'Sala',
            'image_url' => "https://media.example.com/storage/{$scenePath}",
            'sort_order' => 0,
        ]);
        $property->media()->create([
            'type' => 'image',
            'url_or_path' => $propertyImagePath,
            'title' => 'fachada.jpg',
        ]);
        $this->actingAs($owner);

        $response = $this->delete(route('properties.destroy', $property->id));

        $response->assertRedirect(route('properties.index'));
        $this->assertDatabaseMissing('properties', ['id' => $property->id]);
        $this->assertDatabaseMissing('tours', ['id' => $tour->id]);
        $this->assertDatabaseMissing('scenes', ['tour_id' => $tour->id]);
        Storage::disk('r2')->assertMissing($scenePath);
        Storage::disk('r2')->assertMissing($propertyImagePath);
        Storage::disk('r2')->assertExists($unrelatedTourPath);
    }

    public function test_oversized_property_photo_returns_a_friendly_validation_error(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $department = Department::query()->where('name', 'Caldas')->firstOrFail();
        $city = City::query()->where('name', 'Manizales')->firstOrFail();
        $this->actingAs($owner);

        $response = $this->from(route('properties.show', $property->id))
            ->put(route('properties.update', $property), [
                'title' => $property->title,
                'description' => $property->description,
                'price' => $property->price,
                'property_type' => $property->property_type,
                'listing_type' => 'venta',
                'department_id' => $department->id,
                'city_id' => $city->id,
                'neighborhood' => $property->neighborhood,
                'is_furnished' => '0',
                'has_elevator' => '0',
                'images' => [UploadedFile::fake()->image('fachada.jpg')->size(15361)],
            ]);

        $response->assertRedirect(route('properties.show', $property->id))
            ->assertSessionHasErrors([
                'images.0' => 'La fotografía es muy pesada. El tamaño máximo permitido es de 15 MB.',
            ]);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_owner_can_upload_property_video_to_r2(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->mock(Mp4DurationValidator::class)
            ->shouldReceive('isAtMostOneMinute')
            ->once()
            ->andReturn(true);
        $this->actingAs($owner);

        $response = $this->post(route('properties.video.store', $property), [
            'video' => UploadedFile::fake()->create('recorrido.mp4', 100, 'video/mp4'),
        ]);

        $media = $property->media()->where('type', 'video')->firstOrFail();
        $response->assertRedirect(route('dashboard.index'));
        $this->assertStringStartsWith("Videos/{$property->id}/", $media->url_or_path);
        Storage::disk('r2')->assertExists($media->url_or_path);
    }

    public function test_media_upload_endpoint_saves_images_to_the_pictures_prefix(): void
    {
        Storage::fake('r2');
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->post(route('media.store'), [
            'property_id' => $property->id,
            'type' => 'image',
            'file' => UploadedFile::fake()->image('sala.png'),
        ]);

        $media = Media::query()->where('property_id', $property->id)->firstOrFail();
        $response->assertRedirect();
        $this->assertSame('image', $media->type);
        $this->assertStringStartsWith("Pictures/{$property->id}/", $media->url_or_path);
        Storage::disk('r2')->assertExists($media->url_or_path);
    }

    private function createProperty(User $owner): Property
    {
        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        $city = City::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Manizales',
        ]);

        return Property::query()->create([
            'user_id' => $owner->id,
            'title' => 'Apartamento de prueba',
            'description' => 'Inmueble de prueba para archivos.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'status' => 'active',
            'department' => $department->name,
            'city' => $city->name,
            'neighborhood' => 'Chipre',
        ]);
    }
}
