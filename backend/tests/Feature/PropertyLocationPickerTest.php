<?php

namespace Tests\Feature;

use App\Modules\Property\Models\City;
use App\Modules\Property\Models\Department;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PropertyLocationPickerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_and_edit_forms_render_the_location_picker_with_cartodb_tiles(): void
    {
        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        City::query()->firstOrCreate(['department_id' => $department->id, 'name' => 'Manizales']);
        $owner = User::factory()->create();
        $property = $this->createProperty($owner, [
            'department' => 'Caldas',
            'city' => 'Manizales',
            'address' => 'Carrera 23 # 60-10',
            'latitude' => 5.0601234,
            'longitude' => -75.5012345,
        ]);

        $this->actingAs($owner)
            ->get(route('properties.create'))
            ->assertOk()
            ->assertSee('property-location-map')
            ->assertSee('Buscar dirección')
            ->assertSee('OpenStreetMap')
            ->assertSee('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', false)
            ->assertDontSee('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png', false)
            ->assertSee('Marca un punto en el mapa para guardar el inmueble.')
            ->assertSee('data-geocode-url="/location-catalog/geocode"', false);

        $this->get(route('properties.edit', $property))
            ->assertOk()
            ->assertSee('value="Carrera 23 # 60-10"', false)
            ->assertSee('data-selected-latitude="5.0601234"', false)
            ->assertSee('data-selected-longitude="-75.5012345"', false)
            ->assertSee('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', false)
            ->assertDontSee('https://{s}.basemaps.cartocdn.com/rastertiles/voyager_nolabels/{z}/{x}/{y}{r}.png', false)
            ->assertSee('Marca un punto en el mapa para guardar el inmueble.')
            ->assertSee('data-geocode-url="/location-catalog/geocode"', false);
    }

    public function test_video_upload_form_uses_https_when_request_is_forwarded_by_a_tls_proxy(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $filmmaker = User::factory()->create(['role' => User::ROLE_FILMMAKER]);
        $filmmaker->assignedProperties()->attach($property);

        $this->actingAs($filmmaker)
            ->withServerVariables([
                'HTTP_HOST' => 'colombiasincomision.com',
                'SERVER_PORT' => '80',
                'HTTPS' => 'off',
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ])
            ->get(route('properties.edit', $property, false))
            ->assertOk()
            ->assertSee(
                'action="https://localhost:8000/properties/'.$property->id.'/video"',
                false,
            );
    }

    public function test_selected_address_and_coordinates_are_saved_when_creating_a_property(): void
    {
        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        $city = City::query()->firstOrCreate(['department_id' => $department->id, 'name' => 'Manizales']);
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('properties.store'), [
                'title' => 'Apartamento con ubicación',
                'description' => 'Ubicación seleccionada en el mapa.',
                'price' => 250000000,
                'property_type' => 'apartamento',
                'listing_type' => 'venta',
                'department_id' => $department->id,
                'city_id' => $city->id,
                'neighborhood' => 'Chipre',
                'address' => 'Carrera 23 # 60-10',
                'latitude' => '5.0601234',
                'longitude' => '-75.5012345',
                'is_furnished' => '0',
                'has_elevator' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('properties', [
            'title' => 'Apartamento con ubicación',
            'address' => 'Carrera 23 # 60-10',
            'latitude' => 5.0601234,
            'longitude' => -75.5012345,
        ]);
    }

    public function test_geocoder_uses_the_selected_city_and_department(): void
    {
        $query = 'Calle 10 # 20-30, Manizales, Caldas, Colombia';
        Cache::forget('location-geocode:'.sha1(mb_strtolower($query)));
        RateLimiter::clear('openstreetmap-nominatim-global');
        Http::preventStrayRequests();
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                ['lat' => '5.0601234', 'lon' => '-75.5012345'],
            ]),
        ]);

        $response = $this->getJson(route('location-catalog.geocode', [
            'address' => 'Calle 10 # 20-30',
            'city' => 'Manizales',
            'department' => 'Caldas',
        ]));

        $response->assertOk()
            ->assertJson([
                'latitude' => 5.0601234,
                'longitude' => -75.5012345,
            ]);
        Http::assertSent(function (Request $request) use ($query): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $queryParameters);

            return ($queryParameters['q'] ?? null) === $query;
        });
    }

    public function test_geocoder_reports_when_no_location_is_found(): void
    {
        $query = 'Lugar inexistente, Bogotá, Cundinamarca, Colombia';
        Cache::forget('location-geocode:'.sha1(mb_strtolower($query)));
        RateLimiter::clear('openstreetmap-nominatim-global');
        Http::preventStrayRequests();
        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([]),
        ]);

        $this->getJson(route('location-catalog.geocode', [
            'address' => 'Lugar inexistente',
            'city' => 'Bogotá',
            'department' => 'Cundinamarca',
        ]))
            ->assertNotFound()
            ->assertJsonPath('message', 'No encontramos esa ubicación. Puedes marcarla directamente en el mapa.');
    }

    private function createProperty(User $owner, array $attributes = []): Property
    {
        return $owner->properties()->create(array_merge([
            'title' => 'Apartamento de ubicación',
            'description' => 'Propiedad de prueba.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'listing_type' => 'venta',
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => 'Chipre',
        ], $attributes));
    }
}
