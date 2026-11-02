<?php

namespace Tests\Feature;

use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\City;
use App\Modules\Property\Models\Department;
use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PropertyDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_verification_badges_show_the_correct_state_on_home_catalog_and_details(): void
    {
        $owner = User::factory()->create();
        $unverifiedProperty = $this->createProperty($owner, ['title' => 'Propiedad sin verificar']);
        $verifiedProperty = $this->createProperty($owner, ['title' => 'Propiedad verificada']);
        $verifiedProperty->is_verified = true;
        $verifiedProperty->save();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('No verificada')
            ->assertSee('Verificado')
            ->assertSee(sprintf('CSC-%08d', $unverifiedProperty->id))
            ->assertSee(sprintf('CSC-%08d', $verifiedProperty->id))
            ->assertSee('bg-blue-50 text-blue-700');
        $this->get(route('properties.index'))
            ->assertOk()
            ->assertSee('No verificada')
            ->assertSee('Verificado')
            ->assertSee(sprintf('CSC-%08d', $unverifiedProperty->id))
            ->assertSee(sprintf('CSC-%08d', $verifiedProperty->id))
            ->assertSee('bg-slate-100 text-slate-600');
        $this->get(route('properties.show', $unverifiedProperty))
            ->assertOk()
            ->assertSee('No verificada')
            ->assertSee(sprintf('CSC-%08d', $unverifiedProperty->id));
        $this->get(route('properties.show', $verifiedProperty))
            ->assertOk()
            ->assertSee('Verificado')
            ->assertSee(sprintf('CSC-%08d', $verifiedProperty->id))
            ->assertSee('<circle cx="10" cy="10" r="8"></circle>', false);
    }

    public function test_property_details_are_saved_when_a_property_is_created(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $response = $this->post(route('properties.store'), $this->propertyInput([
            'neighborhood' => 'Barrio escrito manualmente',
            'bedrooms' => 3,
            'bathrooms' => 2,
            'half_bathrooms' => 1,
            'parking_spaces' => 2,
            'built_area_m2' => 120.5,
            'lot_area_m2' => 180,
            'floor_number' => 4,
            'total_floors' => 8,
            'stratum' => 5,
            'year_built' => 2020,
            'administration_fee' => 350000,
            'is_furnished' => '1',
            'has_elevator' => '0',
        ]));

        $property = Property::query()->firstOrFail();
        $response->assertRedirect(route('properties.show', $property));
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'parking_spaces' => 2,
            'neighborhood' => 'Barrio escrito manualmente',
            'built_area_m2' => 120.50,
            'lot_area_m2' => 180.00,
            'administration_fee' => 350000.00,
            'is_furnished' => true,
            'has_elevator' => false,
        ]);
    }

    public function test_property_creation_requires_map_coordinates(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $response = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'latitude' => null,
            'longitude' => null,
        ]));

        $response->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors([
                'latitude' => 'Marca un punto en el mapa para indicar la ubicación del inmueble.',
                'longitude' => 'Completa las coordenadas del punto marcado en el mapa.',
            ]);
        $this->assertDatabaseCount('properties', 0);
    }

    public function test_property_details_can_be_updated(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $department = Department::query()->where('name', 'Caldas')->firstOrFail();
        $city = City::query()->where('department_id', $department->id)->where('name', 'Manizales')->firstOrFail();
        $this->actingAs($owner);

        $response = $this->put(route('properties.update', $property), $this->propertyInput([
            'bedrooms' => 4,
            'bathrooms' => 3,
            'half_bathrooms' => 1,
            'parking_spaces' => 2,
            'built_area_m2' => 145,
            'lot_area_m2' => 200,
            'floor_number' => 2,
            'total_floors' => 5,
            'stratum' => 4,
            'year_built' => 2018,
            'administration_fee' => 425000,
            'is_furnished' => '0',
            'has_elevator' => '1',
            'listing_type' => 'alquiler',
            'status' => 'active',
            'neighborhood' => 'Barrio actualizado manualmente',
        ]));

        $response->assertRedirect(route('properties.show', $property));
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'bedrooms' => 4,
            'bathrooms' => 3,
            'parking_spaces' => 2,
            'built_area_m2' => 145.00,
            'is_furnished' => false,
            'has_elevator' => true,
            'listing_type' => 'alquiler',
            'neighborhood' => 'Barrio actualizado manualmente',
        ]);
    }

    public function test_property_update_requires_map_coordinates(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner, [
            'latitude' => 5.0601234,
            'longitude' => -75.5012345,
        ]);
        $this->actingAs($owner);

        $response = $this->from(route('properties.edit', $property))->put(
            route('properties.update', $property),
            $this->propertyInput([
                'latitude' => null,
                'longitude' => null,
            ]),
        );

        $response->assertRedirect(route('properties.edit', $property))
            ->assertSessionHasErrors([
                'latitude' => 'Marca un punto en el mapa para indicar la ubicación del inmueble.',
                'longitude' => 'Completa las coordenadas del punto marcado en el mapa.',
            ]);
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'latitude' => 5.0601234,
            'longitude' => -75.5012345,
        ]);
    }

    public function test_public_property_details_include_rooms_garages_and_area(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner, [
            'bedrooms' => 3,
            'bathrooms' => 2,
            'parking_spaces' => 1,
            'built_area_m2' => 110,
            'latitude' => 5.0601234,
            'longitude' => -75.5012345,
        ]);

        $response = $this->get(route('properties.show', $property));

        $response->assertOk()
            ->assertSee('Habitaciones')
            ->assertSee('Garajes')
            ->assertSee('Área construida')
            ->assertSee('110,00 m²')
            ->assertSee('Ver en Google Street View')
            ->assertSee('https://www.google.com/maps/@?api=1&amp;map_action=pano&amp;viewpoint=5.0601234,-75.5012345', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertDontSee('Abrir ubicación en OpenStreetMap')
            ->assertDontSee('Buscar zona en OpenStreetMap');
    }

    public function test_property_contact_uses_the_owner_phone_and_gallery_has_single_image_controls(): void
    {
        $owner = User::factory()->create(['phone_number' => '+573001234567']);
        $property = $this->createProperty($owner, ['title' => 'Casa con contacto directo']);
        Media::query()->create([
            'property_id' => $property->id,
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
            'type' => 'image',
            'url_or_path' => 'https://example.com/fachada.jpg',
            'title' => 'Fachada',
            'is_primary' => true,
        ]);
        Media::query()->create([
            'property_id' => $property->id,
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
            'type' => 'image',
            'url_or_path' => 'https://example.com/sala.jpg',
            'title' => 'Sala',
        ]);

        $response = $this->get(route('properties.show', $property));

        $response->assertOk()
            ->assertSee('data-phone="573001234567"', false)
            ->assertSee('data-phone="573113611426"', false)
            ->assertSee('data-gallery-photo-count="2"', false)
            ->assertSee('aria-label="Ver las 2 fotos"', false)
            ->assertSee('class="property-media-tile property-media-tile--count"', false)
            ->assertSee('data-gallery-previous', false)
            ->assertSee('data-gallery-next', false)
            ->assertSee('data-gallery-counter', false)
            ->assertDontSee('Ver en Google Street View')
            ->assertSee('https://example.com/fachada.jpg')
            ->assertSee('https://example.com/sala.jpg');
    }

    public function test_rental_operation_is_saved_shown_and_can_filter_the_catalog(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $this->post(route('properties.store'), $this->propertyInput([
            'title' => 'Casa disponible para alquiler',
            'listing_type' => 'alquiler',
            'price' => 1800000,
        ]));
        $rentalProperty = Property::query()->where('title', 'Casa disponible para alquiler')->firstOrFail();
        $saleProperty = $this->createProperty($owner, [
            'title' => 'Apartamento disponible para venta',
            'listing_type' => 'venta',
        ]);

        $detailResponse = $this->get(route('properties.show', $rentalProperty));
        $catalogResponse = $this->get(route('properties.index', ['listing_type' => 'alquiler']));
        $homeResponse = $this->get(route('home', ['listing_type' => 'alquiler']));

        $this->assertDatabaseHas('properties', [
            'id' => $rentalProperty->id,
            'listing_type' => 'alquiler',
            'price' => 1800000,
        ]);
        $detailResponse->assertOk()
            ->assertSee('En alquiler')
            ->assertSee('property-operation-badge--rental')
            ->assertSee('Canon mensual')
            ->assertSee('/ mes');
        $catalogResponse->assertOk()
            ->assertSee('Casa disponible para alquiler')
            ->assertDontSee('Apartamento disponible para venta');
        $homeResponse->assertOk()
            ->assertSee('Casa disponible para alquiler')
            ->assertSee('property-operation--rental')
            ->assertDontSee('Apartamento disponible para venta');
    }

    public function test_sale_operation_is_available_in_the_public_filters(): void
    {
        $owner = User::factory()->create();
        $saleProperty = $this->createProperty($owner, [
            'title' => 'Apartamento disponible para venta',
            'listing_type' => 'venta',
        ]);
        $this->createProperty($owner, [
            'title' => 'Casa disponible para alquiler',
            'listing_type' => 'alquiler',
        ]);

        $catalogResponse = $this->get(route('properties.index', ['listing_type' => 'venta']));
        $homeResponse = $this->get(route('home', ['listing_type' => 'venta']));

        $catalogResponse->assertOk()
            ->assertSee('value="venta" selected', false)
            ->assertSee('Apartamento disponible para venta')
            ->assertDontSee('Casa disponible para alquiler');
        $homeResponse->assertOk()
            ->assertSee('value="venta" selected', false)
            ->assertSee('value="alquiler"', false)
            ->assertSee('Apartamento disponible para venta')
            ->assertDontSee('Casa disponible para alquiler');

        $this->assertModelExists($saleProperty);
    }

    public function test_sale_price_filter_supports_the_configured_sale_limit(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner, [
            'title' => 'Casa de alto valor en venta',
            'price' => 500000000000,
            'listing_type' => 'venta',
        ]);

        $this->get(route('properties.index', [
            'listing_type' => 'venta',
            'min_price' => 400000000000,
            'max_price' => 600000000000,
        ]))
            ->assertOk()
            ->assertSee('max="1000000000000"', false)
            ->assertSee($property->title);
    }

    public function test_listing_operation_is_required_and_must_be_valid(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $missingOperationResponse = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'listing_type' => null,
        ]));
        $missingOperationResponse->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('listing_type');

        $invalidOperationResponse = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'listing_type' => 'leasing',
        ]));

        $invalidOperationResponse->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('listing_type');
        $this->assertDatabaseCount('properties', 0);
    }

    public function test_rejects_negative_property_detail_values(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $response = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'built_area_m2' => -12,
        ]));

        $response->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('built_area_m2');
        $this->assertDatabaseCount('properties', 0);
    }

    public function test_rejects_prices_outside_the_sale_and_rental_limits(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $belowMinimum = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'price' => 999999,
        ]));
        $belowMinimum->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('price');

        $saleAboveMaximum = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'price' => 50000000001,
        ]));
        $saleAboveMaximum->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('price');

        $rentalAboveMaximum = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'listing_type' => 'alquiler',
            'price' => 100000001,
        ]));
        $rentalAboveMaximum->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseCount('properties', 0);
    }

    public function test_accepts_the_maximum_sale_price_and_rejects_a_construction_year_before_1950(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $response = $this->post(route('properties.store'), $this->propertyInput([
            'price' => 50000000000,
        ]));
        $property = Property::query()->firstOrFail();

        $response->assertRedirect(route('properties.show', $property));
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'price' => 50000000000,
        ]);

        $invalidYear = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'title' => 'Inmueble con año no permitido',
            'year_built' => 1949,
        ]));
        $invalidYear->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('year_built');
        $this->assertDatabaseCount('properties', 1);
    }

    public function test_catalog_filters_by_bedrooms_stratum_and_price_range(): void
    {
        $owner = User::factory()->create();
        $matchingProperty = $this->createProperty($owner, [
            'title' => 'Apartamento filtrado',
            'price' => 250000000,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'stratum' => 4,
        ]);
        $this->createProperty($owner, [
            'title' => 'Casa fuera del filtro',
            'price' => 400000000,
            'bedrooms' => 4,
            'stratum' => 5,
        ]);

        $response = $this->get(route('properties.index', [
            'bedrooms' => 3,
            'bathrooms' => 2,
            'stratum' => 4,
            'min_price' => 200000000,
            'max_price' => 300000000,
        ]));

        $response->assertOk()
            ->assertSee($matchingProperty->title)
            ->assertDontSee('Casa fuera del filtro');
    }

    public function test_property_locations_must_follow_the_department_and_city_hierarchy(): void
    {
        $owner = User::factory()->create();
        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        $otherDepartment = Department::query()->firstOrCreate(['name' => 'Risaralda']);
        $city = City::query()->firstOrCreate(['department_id' => $department->id, 'name' => 'Manizales']);
        $otherCity = City::query()->firstOrCreate(['department_id' => $otherDepartment->id, 'name' => 'Pereira']);
        $this->actingAs($owner);

        $response = $this->from(route('properties.create'))->post(route('properties.store'), $this->propertyInput([
            'department_id' => $department->id,
            'city_id' => $otherCity->id,
        ]));

        $response->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors('city_id');
        $this->assertDatabaseCount('properties', 0);
    }

    public function test_city_options_are_scoped_to_the_selected_department(): void
    {
        $caldas = Department::query()->where('name', 'Caldas')->firstOrFail();
        $risaralda = Department::query()->where('name', 'Risaralda')->firstOrFail();

        $this->getJson(route('location-catalog.cities', $caldas))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Manizales'])
            ->assertJsonMissing(['name' => 'Pereira']);

        $this->getJson(route('location-catalog.cities', $risaralda))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Pereira'])
            ->assertJsonMissing(['name' => 'Manizales']);
    }

    public function test_property_forms_render_city_options_without_a_catalog_request(): void
    {
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $department = Department::query()->where('name', 'Caldas')->firstOrFail();
        $city = City::query()->where('department_id', $department->id)->where('name', 'Manizales')->firstOrFail();
        $this->actingAs($owner);

        $createResponse = $this->get(route('properties.create'));
        $editResponse = $this->get(route('properties.edit', $property));

        $createResponse->assertOk()
            ->assertSee('data-city-options', false)
            ->assertSee('Manizales')
            ->assertSee('data-department-id="'.$department->id.'"', false);
        $editResponse->assertOk()
            ->assertSee('data-city-options', false)
            ->assertSee('Manizales')
            ->assertSee('data-selected-city="'.$city->id.'"', false);
    }

    public function test_location_catalog_management_requires_the_configured_admin_email(): void
    {
        $admin = User::factory()->create(['email' => 'locations-admin@example.test']);
        $this->withConfig(['location.admin_emails' => [$admin->email]])
            ->actingAs($admin)
            ->get(route('location-catalog.index'))
            ->assertOk()
            ->assertSee('Catálogo de ubicaciones');

        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        $this->withConfig(['location.admin_emails' => [$admin->email]])
            ->actingAs($admin)
            ->post(route('location-catalog.cities.store'), [
                'department_id' => $department->id,
                'name' => 'Villamaría',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('cities', [
            'department_id' => $department->id,
            'name' => 'Villamaría',
        ]);

        $otherUser = User::factory()->create(['email' => 'property-owner@example.test']);
        $this->withConfig(['location.admin_emails' => [$admin->email]])
            ->actingAs($otherUser)
            ->get(route('location-catalog.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, int|float|string|null>  $overrides
     * @return array<string, int|float|string|null>
     */
    private function propertyInput(array $overrides = []): array
    {
        $locations = $this->locationIds();

        return array_merge([
            'title' => 'Apartamento de prueba',
            'description' => 'Propiedad con detalles completos.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'listing_type' => 'venta',
            'department_id' => $locations['department_id'],
            'city_id' => $locations['city_id'],
            'neighborhood' => 'Chipre',
            'latitude' => '5.0601234',
            'longitude' => '-75.5012345',
            'bedrooms' => null,
            'bathrooms' => null,
            'half_bathrooms' => null,
            'parking_spaces' => null,
            'built_area_m2' => null,
            'lot_area_m2' => null,
            'floor_number' => null,
            'total_floors' => null,
            'stratum' => null,
            'year_built' => null,
            'administration_fee' => null,
            'is_furnished' => '0',
            'has_elevator' => '0',
        ], $overrides);
    }

    /**
     * @param  array<string, int|float|string|null>  $attributes
     */
    private function createProperty(User $owner, array $attributes = []): Property
    {
        $this->locationIds();

        return $owner->properties()->create(array_merge([
            'title' => 'Apartamento de prueba',
            'description' => 'Propiedad con detalles completos.',
            'price' => 250000000,
            'property_type' => 'apartamento',
            'listing_type' => 'venta',
            'status' => 'active',
            'department' => 'Caldas',
            'city' => 'Manizales',
            'neighborhood' => 'Chipre',
        ], $attributes));
    }

    /**
     * @return array{department_id: int, city_id: int}
     */
    private function locationIds(): array
    {
        $department = Department::query()->firstOrCreate(['name' => 'Caldas']);
        $city = City::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Manizales',
        ]);

        return [
            'department_id' => $department->id,
            'city_id' => $city->id,
        ];
    }
}
