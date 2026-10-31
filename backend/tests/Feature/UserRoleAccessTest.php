<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Property\Models\Property;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_register_page_includes_a_colombian_phone_number_field(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk()
            ->assertSee('Crear una cuenta')
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="phone_number"', false)
            ->assertSee('+57 300 123 4567')
            ->assertSee('name="password_confirmation"', false)
            ->assertSee(route('login'));
    }

    public function test_public_registration_always_creates_a_regular_user(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Cuenta Pública',
            'email' => 'public@example.test',
            'phone_number' => '300 123 4567',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('users', [
            'email' => 'public@example.test',
            'phone_number' => '+573001234567',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_public_registration_rejects_a_non_colombian_phone_number(): void
    {
        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'Cuenta Pública',
            'email' => 'public@example.test',
            'phone_number' => '+1 2025550123',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors([
                'phone_number' => 'El número de teléfono debe ser un celular colombiano, por ejemplo +57 300 123 4567.',
            ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'public@example.test',
        ]);
    }

    public function test_public_registration_rejects_a_duplicate_phone_number(): void
    {
        User::factory()->create([
            'phone_number' => '+573001234567',
        ]);

        $response = $this->from(route('register'))->post(route('register'), [
            'name' => 'Otra Cuenta',
            'email' => 'otra@example.test',
            'phone_number' => '+57 300 123 4567',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors('phone_number');
        $this->assertDatabaseMissing('users', [
            'email' => 'otra@example.test',
        ]);
    }

    public function test_non_admin_cannot_open_account_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_filmmaker_accounts_and_assign_properties(): void
    {
        $admin = $this->createAdmin();
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $this->actingAs($admin);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Cineasta de Prueba',
            'email' => 'filmmaker@example.test',
            'role' => User::ROLE_FILMMAKER,
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'filmmaker@example.test',
            'role' => User::ROLE_FILMMAKER,
        ]);

        $filmmaker = User::query()->where('email', 'filmmaker@example.test')->firstOrFail();

        $assignmentResponse = $this->put(
            route('admin.users.properties.update', $filmmaker),
            ['property_ids' => [$property->id]],
        );

        $assignmentResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('filmmaker_property', [
            'filmmaker_id' => $filmmaker->id,
            'property_id' => $property->id,
        ]);
    }

    public function test_admin_can_render_account_management(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('Cuentas y asignaciones')
            ->assertSee('Cineasta');
    }

    public function test_filmmaker_dashboard_only_shows_assigned_properties(): void
    {
        $owner = User::factory()->create();
        $assignedProperty = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Inmueble Asignado',
        ]);
        Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Inmueble No Asignado',
        ]);
        $filmmaker = $this->createFilmmaker();
        $filmmaker->assignedProperties()->attach($assignedProperty);

        $response = $this->actingAs($filmmaker)->get(route('dashboard.index'));

        $response->assertOk()
            ->assertSee('Inmueble Asignado')
            ->assertDontSee('Inmueble No Asignado')
            ->assertDontSee('Publicar inmueble');
    }

    public function test_filmmaker_can_open_tour_form_for_an_assigned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = $this->createFilmmaker();
        $filmmaker->assignedProperties()->attach($property);

        $response = $this->actingAs($filmmaker)->get(route('tours360.create', [
            'property_id' => $property->id,
        ]));

        $response->assertOk();
    }

    public function test_filmmaker_can_submit_video_upload_for_an_assigned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = $this->createFilmmaker();
        $filmmaker->assignedProperties()->attach($property);

        $response = $this->actingAs($filmmaker)->post(
            route('properties.video.store', $property),
            [],
        );

        $response->assertSessionHasErrors('video');
    }

    public function test_filmmaker_cannot_upload_a_video_to_an_unassigned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = $this->createFilmmaker();

        $response = $this->actingAs($filmmaker)->post(
            route('properties.video.store', $property),
            [],
        );

        $response->assertForbidden();
    }

    public function test_filmmaker_cannot_use_the_generic_media_upload_route(): void
    {
        $filmmaker = $this->createFilmmaker();

        $response = $this->actingAs($filmmaker)->post(route('media.store'), []);

        $response->assertForbidden();
    }

    public function test_filmmaker_cannot_create_a_tour_for_an_unassigned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = $this->createFilmmaker();

        $response = $this->actingAs($filmmaker)->get(route('tours360.create', [
            'property_id' => $property->id,
        ]));

        $response->assertForbidden();
    }

    public function test_filmmaker_cannot_edit_an_assigned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = $this->createFilmmaker();
        $filmmaker->assignedProperties()->attach($property);

        $response = $this->actingAs($filmmaker)->get(route('properties.edit', $property));

        $response->assertForbidden();
    }

    public function test_admin_dashboard_shows_properties_owned_by_other_users(): void
    {
        $firstOwner = User::factory()->create();
        $secondOwner = User::factory()->create();
        Property::factory()->create([
            'user_id' => $firstOwner->id,
            'title' => 'Primera propiedad',
        ]);
        Property::factory()->create([
            'user_id' => $secondOwner->id,
            'title' => 'Segunda propiedad',
        ]);
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard.index'));

        $response->assertOk()
            ->assertSee('Primera propiedad')
            ->assertSee('Segunda propiedad')
            ->assertSee(route('admin.users.index'));
    }

    public function test_admin_login_redirects_to_the_management_dashboard(): void
    {
        $admin = $this->createAdmin();

        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.index'));
    }

    public function test_admin_can_edit_a_property_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('properties.edit', $property));

        $response->assertOk();
    }

    public function test_admin_bootstrap_command_promotes_an_existing_account(): void
    {
        $user = User::factory()->unverified()->create();

        $this->artisan('users:promote-admin', ['email' => $user->email])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        return $admin;
    }

    private function createFilmmaker(): User
    {
        $filmmaker = User::factory()->create();
        $filmmaker->role = User::ROLE_FILMMAKER;
        $filmmaker->save();

        return $filmmaker;
    }
}
