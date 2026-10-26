<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Property\Models\City;
use App\Modules\Property\Models\Department;
use App\Modules\Property\Models\Property;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyBlueprintTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_create_a_property_with_a_blueprint_pdf(): void
    {
        $owner = User::factory()->create();
        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        $city = City::query()->firstOrCreate(['department_id' => $department->id, 'name' => 'Manizales']);
        Storage::fake('local');
        $this->actingAs($owner);

        $response = $this->post(route('properties.store'), [
            'title' => 'Apartamento con planos',
            'description' => 'Inmueble publicado con su plano arquitectónico.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'listing_type' => 'venta',
            'department_id' => $department->id,
            'city_id' => $city->id,
            'neighborhood' => 'Chipre',
            'is_furnished' => '0',
            'has_elevator' => '0',
            'blueprint' => UploadedFile::fake()->createWithContent('planos.pdf', $this->pdfWithPages(2)),
        ]);

        $property = Property::query()->where('title', 'Apartamento con planos')->firstOrFail();
        $media = $property->media()->where('type', 'blueprint')->firstOrFail();
        $response->assertRedirect(route('properties.show', $property));
        $this->assertDatabaseHas('media', [
            'property_id' => $property->id,
            'type' => 'blueprint',
        ]);
        Storage::disk('local')->assertExists($media->url_or_path);
    }

    public function test_owner_can_upload_a_pdf_with_ten_or_fewer_pages(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Storage::fake('local');
        $this->actingAs($owner);

        $response = $this->post(route('properties.blueprint.store', $property), [
            'property_id' => $property->id,
            'blueprint' => UploadedFile::fake()->createWithContent('planos.pdf', $this->pdfWithPages(10)),
        ]);

        $media = $property->media()->where('type', 'blueprint')->firstOrFail();
        $response->assertRedirect(route('properties.edit', $property));
        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'property_id' => $property->id,
            'type' => 'blueprint',
            'url_or_path' => $media->url_or_path,
        ]);
        Storage::disk('local')->assertExists($media->url_or_path);

        $this->get(route('properties.blueprint.show', $property))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_owner_cannot_upload_a_pdf_with_more_than_ten_pages(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Storage::fake('local');
        $this->actingAs($owner);

        $response = $this->from(route('properties.edit', $property))->post(route('properties.blueprint.store', $property), [
            'property_id' => $property->id,
            'blueprint' => UploadedFile::fake()->createWithContent('planos.pdf', $this->pdfWithPages(11)),
        ]);

        $response->assertRedirect(route('properties.edit', $property))
            ->assertSessionHasErrors('blueprint');
        $this->assertDatabaseMissing('media', [
            'property_id' => $property->id,
            'type' => 'blueprint',
        ]);
        Storage::disk('local')->assertDirectoryEmpty("properties/{$property->id}/blueprints");
    }

    public function test_owner_cannot_upload_a_pdf_larger_than_ten_megabytes(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $response = $this->from(route('properties.edit', $property))->post(route('properties.blueprint.store', $property), [
            'property_id' => $property->id,
            'blueprint' => UploadedFile::fake()->create('planos.pdf', 10241, 'application/pdf'),
        ]);

        $response->assertRedirect(route('properties.edit', $property))
            ->assertSessionHasErrors('blueprint');
        $this->assertDatabaseMissing('media', [
            'property_id' => $property->id,
            'type' => 'blueprint',
        ]);
    }

    public function test_user_cannot_upload_a_blueprint_for_another_users_property(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($otherUser);

        $response = $this->post(route('properties.blueprint.store', $property), [
            'property_id' => $property->id,
            'blueprint' => UploadedFile::fake()->createWithContent('planos.pdf', $this->pdfWithPages(1)),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('media', [
            'property_id' => $property->id,
            'type' => 'blueprint',
        ]);
    }

    public function test_blueprint_upload_is_available_in_edit_but_not_in_the_dashboard(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $this->actingAs($owner);

        $this->get(route('properties.edit', $property))
            ->assertOk()
            ->assertSee(route('properties.blueprint.store', $property), false)
            ->assertSee('Seleccionar planos');

        $this->get(route('properties.create'))
            ->assertOk()
            ->assertSee('name="blueprint"', false)
            ->assertSee('Planos arquitectónicos (PDF, opcional)');

        $this->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Agregar PDF')
            ->assertDontSee('Actualizar planos');
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
            'description' => 'Inmueble de prueba para planos.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => 'Chipre',
        ]);
    }

    private function pdfWithPages(int $pageCount): string
    {
        $pdf = "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $pdf .= "2 0 obj << /Type /Pages /Count {$pageCount} /Kids [] >> endobj\n";

        for ($page = 0; $page < $pageCount; $page++) {
            $objectId = $page + 3;
            $pdf .= "{$objectId} 0 obj << /Type /Page /Parent 2 0 R >> endobj\n";
        }

        return $pdf."trailer << /Root 1 0 R >>\n%%EOF";
    }
}
