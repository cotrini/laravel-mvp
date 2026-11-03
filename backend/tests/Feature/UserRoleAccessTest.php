<?php

namespace Tests\Feature;

use App\Modules\Property\Models\Property;
use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\UnencryptedToken;
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
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => 'Cuenta Pública',
            'email' => 'public@example.test',
            'phone_number' => '300 123 4567',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'public@example.test',
            'phone_number' => '+573001234567',
            'role' => User::ROLE_USER,
            'email_verified_at' => null,
            'email_verification_required' => true,
        ]);
        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('data-remaining-seconds="60"', false);
        $user = User::query()->where('email', 'public@example.test')->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function (VerifyEmail $notification) use ($user): bool {
                $mailMessage = $notification->toMail($user);

                $this->assertSame(
                    'Confirma tu correo electrónico | Colombia Sin Comisión',
                    $mailMessage->subject,
                );
                $this->assertSame([
                    'html' => 'mail.verify-email',
                    'text' => 'mail.verify-email-text',
                ], $mailMessage->view);

                $html = view($mailMessage->view['html'], $mailMessage->viewData)->render();
                $text = view($mailMessage->view['text'], $mailMessage->viewData)->render();
                $verificationUrl = $mailMessage->viewData['verificationUrl'];

                $this->assertStringContainsString('Colombia', $html);
                $this->assertStringContainsString('Sin Comisión', $html);
                $this->assertStringContainsString('Estimado/a Cuenta Pública', $html);
                $this->assertStringContainsString('Confirmar correo electrónico', $html);
                $this->assertStringContainsString($verificationUrl, $html);
                $this->assertStringContainsString('Estimado/a Cuenta Pública', $text);
                $this->assertStringContainsString($verificationUrl, $text);
                $this->assertTrue(HttpRequest::create($verificationUrl)->hasValidSignature());

                return true;
            },
        );
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

    public function test_unverified_users_must_verify_and_signed_links_activate_their_accounts(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertRedirect(route('verification.notice'));

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->get($verificationUrl)->assertRedirect(route('dashboard.index'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_users_can_resend_verification_emails(): void
    {
        $this->freezeTime();
        $user = User::factory()->unverified()->create();
        $rateLimitKey = "email-verification-resend:{$user->id}";
        RateLimiter::clear($rateLimitKey);
        Notification::fake();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Te enviamos un nuevo enlace de verificación.');

        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('data-remaining-seconds="60"', false)
            ->assertSee('data-resend-button', false);

        $this->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Ya enviamos una solicitud recientemente. Intenta de nuevo en 60 segundos.');

        Notification::assertSentToTimes($user, VerifyEmail::class, 1);

        $this->travel(60)->seconds();

        $this->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('success', 'Te enviamos un nuevo enlace de verificación.');

        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
    }

    public function test_google_sign_in_does_not_require_email_verification(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'google@example.test',
        ]);
        $verifiedToken = \Mockery::mock(UnencryptedToken::class);
        $verifiedToken->shouldReceive('claims')
            ->once()
            ->andReturn(new DataSet(['sub' => 'google-user-id'], ''));
        $firebaseAuth = $this->mock(FirebaseAuthContract::class);
        $firebaseAuth->shouldReceive('verifyIdToken')
            ->once()
            ->with('google-id-token')
            ->andReturn($verifiedToken);
        $firebaseAuth->shouldReceive('getUser')
            ->once()
            ->with('google-user-id')
            ->andReturn(UserRecord::fromResponseData([
                'localId' => 'google-user-id',
                'email' => 'google@example.test',
                'emailVerified' => true,
                'displayName' => 'Google User',
                'createdAt' => '2025-01-01T00:00:00Z',
            ]));
        Notification::fake();

        $response = $this->postJson(route('firebase-auth'), [
            'idToken' => 'google-id-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('redirect', route('dashboard.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verification_required' => false,
        ]);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('dashboard.index'))->assertOk();
        Notification::assertNothingSent();
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
        Notification::fake();

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Filmmaker de Prueba',
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
        $this->assertFalse($filmmaker->requiresEmailVerification());
        $this->assertTrue($filmmaker->hasVerifiedEmail());
        Notification::assertNothingSent();

        $this->get(route('admin.users.index', [
            'assignment_user_id' => $filmmaker->id,
            'property_code' => $property->identification_code,
        ]))
            ->assertOk()
            ->assertSee($property->title)
            ->assertSee('Asignar propiedad');

        $assignmentResponse = $this->post(
            route('admin.users.properties.store', $filmmaker),
            ['property_code' => $property->identification_code],
        );

        $assignmentResponse->assertRedirect(route('admin.users.index', [
            'assignment_user_id' => $filmmaker->id,
            'property_code' => $property->identification_code,
        ]));
        $this->assertDatabaseHas('filmmaker_property', [
            'filmmaker_id' => $filmmaker->id,
            'property_id' => $property->id,
        ]);
    }

    public function test_property_code_search_stays_on_the_page_with_its_filmmaker(): void
    {
        $admin = $this->createAdmin();
        $admin->name = '0 Admin';
        $admin->save();
        $filmmaker = $this->createFilmmaker();
        $filmmaker->name = 'Z Filmmaker';
        $filmmaker->save();
        User::factory()->count(15)->sequence(
            ...array_map(
                fn (int $index): array => ['name' => sprintf('A User %02d', $index)],
                range(1, 15),
            ),
        )->create();
        $property = Property::factory()->create([
            'user_id' => User::factory()->create(['name' => '1 Property Owner'])->id,
            'title' => 'Propiedad en página secundaria',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'page' => 2,
            'assignment_user_id' => $filmmaker->id,
            'property_code' => $property->identification_code,
        ]));

        $response->assertOk()
            ->assertSee($property->title)
            ->assertSee('name="page" value="2"', false);
    }

    public function test_assigning_one_property_keeps_the_filmmakers_other_assignments(): void
    {
        $admin = $this->createAdmin();
        $filmmaker = $this->createFilmmaker();
        $owner = User::factory()->create();
        $existingProperty = Property::factory()->create(['user_id' => $owner->id]);
        $newProperty = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker->assignedProperties()->attach($existingProperty);

        $response = $this->actingAs($admin)->post(
            route('admin.users.properties.store', $filmmaker),
            ['property_code' => $newProperty->identification_code],
        );

        $response->assertRedirect(route('admin.users.index', [
            'assignment_user_id' => $filmmaker->id,
            'property_code' => $newProperty->identification_code,
        ]));
        $this->assertDatabaseHas('filmmaker_property', [
            'filmmaker_id' => $filmmaker->id,
            'property_id' => $existingProperty->id,
        ]);
        $this->assertDatabaseHas('filmmaker_property', [
            'filmmaker_id' => $filmmaker->id,
            'property_id' => $newProperty->id,
        ]);
    }

    public function test_admin_can_remove_one_filmmaker_property_assignment_without_removing_others(): void
    {
        $admin = $this->createAdmin();
        $filmmaker = $this->createFilmmaker();
        $owner = User::factory()->create();
        $propertyToRemove = Property::factory()->create(['user_id' => $owner->id]);
        $propertyToKeep = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker->assignedProperties()->attach([$propertyToRemove->id, $propertyToKeep->id]);

        $response = $this->actingAs($admin)->delete(
            route('admin.users.properties.destroy', [$filmmaker, $propertyToRemove]),
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('filmmaker_property', [
            'filmmaker_id' => $filmmaker->id,
            'property_id' => $propertyToRemove->id,
        ]);
        $this->assertDatabaseHas('filmmaker_property', [
            'filmmaker_id' => $filmmaker->id,
            'property_id' => $propertyToKeep->id,
        ]);
    }

    public function test_assigning_an_unknown_property_code_does_not_create_an_assignment(): void
    {
        $admin = $this->createAdmin();
        $filmmaker = $this->createFilmmaker();

        $response = $this->actingAs($admin)->from(route('admin.users.index'))->post(
            route('admin.users.properties.store', $filmmaker),
            ['property_code' => 'CSC-99999999'],
        );

        $response->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('property_code');
        $this->assertDatabaseCount('filmmaker_property', 0);
    }

    public function test_admin_cannot_assign_properties_to_an_account_without_an_assignment_role(): void
    {
        $admin = $this->createAdmin();
        $regularUser = User::factory()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.users.properties.store', $regularUser),
            ['property_code' => 'CSC-00000001'],
        );

        $response->assertNotFound();
    }

    public function test_non_admin_cannot_assign_a_property_to_a_filmmaker(): void
    {
        $regularUser = User::factory()->create();
        $filmmaker = $this->createFilmmaker();
        $property = Property::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($regularUser)->post(
            route('admin.users.properties.store', $filmmaker),
            ['property_code' => $property->identification_code],
        );

        $response->assertForbidden();
        $this->assertDatabaseCount('filmmaker_property', 0);
    }

    public function test_admin_cannot_remove_a_filmmaker_assignment_that_does_not_exist(): void
    {
        $admin = $this->createAdmin();
        $filmmaker = $this->createFilmmaker();
        $property = Property::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($admin)->delete(
            route('admin.users.properties.destroy', [$filmmaker, $property]),
        );

        $response->assertNotFound();
        $this->assertDatabaseCount('filmmaker_property', 0);
    }

    public function test_admin_can_render_account_management(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('Cuentas y asignaciones')
            ->assertSee('Filmmaker');
    }

    public function test_admin_can_update_all_editable_fields_without_requiring_verification_for_admin_managed_accounts(): void
    {
        $admin = $this->createAdmin();
        $managedUser = User::factory()->create(['email' => 'before@example.test']);
        Notification::fake();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $managedUser), [
            'name' => 'Nombre actualizado',
            'email' => 'after@example.test',
            'phone_number' => '+57 311 111 2233',
            'role' => User::ROLE_FILMMAKER,
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'name' => 'Nombre actualizado',
            'email' => 'after@example.test',
            'phone_number' => '+573111112233',
            'role' => User::ROLE_FILMMAKER,
            'email_verification_required' => false,
        ]);
        $this->assertNotNull($managedUser->fresh()->email_verified_at);
        $this->assertTrue($managedUser->fresh()->hasVerifiedEmail());
        Notification::assertNothingSent();
    }

    public function test_admin_email_changes_keep_verification_required_for_form_registered_accounts(): void
    {
        $admin = $this->createAdmin();
        $managedUser = User::factory()->unverified()->create([
            'email' => 'before@example.test',
        ]);
        Notification::fake();

        $response = $this->actingAs($admin)->put(route('admin.users.update', $managedUser), [
            'name' => $managedUser->name,
            'email' => 'after@example.test',
            'phone_number' => $managedUser->phone_number,
            'role' => User::ROLE_USER,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'email' => 'after@example.test',
            'email_verified_at' => null,
            'email_verification_required' => true,
        ]);
        Notification::assertSentTo($managedUser, VerifyEmail::class);
    }

    public function test_admin_can_ban_a_user_and_banned_users_are_logged_out(): void
    {
        $admin = $this->createAdmin();
        $managedUser = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.ban', $managedUser))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $managedUser->id,
            'is_banned' => true,
        ]);

        $managedUser->refresh();

        $this->actingAs($managedUser)
            ->get(route('dashboard.index'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_cannot_ban_their_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->patch(route('admin.users.ban', $admin))
            ->assertForbidden();

        $this->assertFalse($admin->fresh()->is_banned);
    }

    public function test_banned_users_cannot_log_in(): void
    {
        $bannedUser = User::factory()->create(['is_banned' => true]);

        $this->post(route('login'), [
            'email' => $bannedUser->email,
            'password' => 'password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_user_listing_is_paginated(): void
    {
        $admin = $this->createAdmin();
        User::factory()->count(16)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('page=2');
    }

    public function test_admin_can_filter_users_by_name_email_or_phone(): void
    {
        $admin = $this->createAdmin();
        $nameMatch = User::factory()->create(['name' => 'Coincidencia por nombre']);
        $emailMatch = User::factory()->create([
            'name' => 'Coincidencia por correo',
            'email' => 'buscar-correo@example.test',
        ]);
        $phoneMatch = User::factory()->create([
            'name' => 'Coincidencia por teléfono',
            'phone_number' => '+573009988776',
        ]);
        User::factory()->create([
            'name' => 'Usuario no coincidente',
            'email' => 'otro@example.test',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Coincidencia por nombre',
        ]));

        $response->assertOk()
            ->assertSee($nameMatch->name)
            ->assertDontSee($emailMatch->name)
            ->assertDontSee($phoneMatch->name);

        $response = $this->get(route('admin.users.index', ['search' => 'buscar-correo']));

        $response->assertOk()
            ->assertSee($emailMatch->name)
            ->assertDontSee($nameMatch->name);

        $response = $this->get(route('admin.users.index', ['search' => '3009988776']));

        $response->assertOk()
            ->assertSee($phoneMatch->name)
            ->assertDontSee($nameMatch->name);
    }

    public function test_admin_user_search_is_preserved_in_pagination_links(): void
    {
        $admin = $this->createAdmin();

        foreach (range(1, 16) as $index) {
            User::factory()->create(['name' => "Usuario filtro {$index}"]);
        }

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'search' => 'Usuario filtro',
        ]));

        $response->assertOk()
            ->assertSee('search=Usuario%20filtro', false)
            ->assertSee('page=2', false);
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
            ->assertDontSee('Publicar inmueble')
            ->assertSee(route('properties.edit', $assignedProperty), false)
            ->assertDontSee('Eliminar');
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

    public function test_standard_user_can_submit_video_upload_for_an_owned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->post(
            route('properties.video.store', $property),
            [],
        );

        $response->assertSessionHasErrors('video');
    }

    public function test_standard_user_cannot_upload_a_video_to_someone_elses_property(): void
    {
        $property = Property::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->actingAs(User::factory()->create())->post(
            route('properties.video.store', $property),
            [],
        );

        $response->assertForbidden();
    }

    public function test_filmmaker_cannot_create_a_property(): void
    {
        $filmmaker = $this->createFilmmaker();

        $response = $this->actingAs($filmmaker)->post(route('properties.store'), []);

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

    public function test_filmmaker_can_edit_an_assigned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = $this->createFilmmaker();
        $filmmaker->assignedProperties()->attach($property);

        $response = $this->actingAs($filmmaker)->get(route('properties.edit', $property));

        $response->assertOk()
            ->assertSee('Video del inmueble')
            ->assertSee(route('properties.video.store', $property), false);
    }

    public function test_standard_user_edit_form_shows_video_upload_controls_for_owned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('properties.edit', $property));

        $response->assertOk()
            ->assertSee('Video del inmueble')
            ->assertSee(route('properties.video.store', $property), false);
    }

    public function test_filmmaker_cannot_delete_an_assigned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);
        $filmmaker = $this->createFilmmaker();
        $filmmaker->assignedProperties()->attach($property);

        $response = $this->actingAs($filmmaker)->delete(route('properties.destroy', $property));

        $response->assertForbidden();
        $this->assertModelExists($property);
    }

    public function test_standard_user_cannot_open_a_tour_creation_form_for_an_owned_property(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('tours360.create', [
            'property_id' => $property->id,
        ]));

        $response->assertForbidden();
    }

    public function test_standard_user_dashboard_shows_video_control_but_hides_tour_creation_controls(): void
    {
        $owner = User::factory()->create();
        Property::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('dashboard.index'));

        $response->assertOk()
            ->assertSee('Agregar video')
            ->assertDontSee('Agregar recorrido 360°');
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

    public function test_admin_can_find_properties_by_identification_code(): void
    {
        $owner = User::factory()->create();
        $property = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Inmueble identificado',
        ]);
        Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Inmueble fuera de la búsqueda',
        ]);
        $admin = $this->createAdmin();
        $identificationCode = sprintf('CSC-%08d', $property->id);

        $response = $this->actingAs($admin)->get(route('dashboard.index', [
            'search' => $identificationCode,
        ]));

        $response->assertOk()
            ->assertSee('Inmueble identificado')
            ->assertSee($identificationCode)
            ->assertDontSee('Inmueble fuera de la búsqueda');
    }

    public function test_admin_can_find_properties_by_title_location_and_owner_details(): void
    {
        $owner = User::factory()->create([
            'name' => 'Propietario objetivo',
            'email' => 'propietario.objetivo@example.test',
        ]);
        $property = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Casa de búsqueda',
            'city' => 'Cartagena',
        ]);
        Property::factory()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Otro inmueble',
            'city' => 'Cali',
        ]);
        $admin = $this->createAdmin();

        foreach ([
            'Casa de búsqueda',
            'Cartagena',
            'Propietario objetivo',
            'propietario.objetivo@example.test',
        ] as $term) {
            $this->actingAs($admin)
                ->get(route('dashboard.index', ['search' => $term]))
                ->assertOk()
                ->assertSee($property->title)
                ->assertDontSee('Otro inmueble');
        }
    }

    public function test_property_search_filters_do_not_override_the_owner_scope(): void
    {
        $owner = User::factory()->create();
        $ownedProperty = Property::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Inmueble propio',
        ]);
        Property::factory()->create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Inmueble ajeno',
        ]);

        $response = $this->actingAs($owner)->get(route('dashboard.index', [
            'search' => 'Inmueble ajeno',
        ]));

        $response->assertOk()
            ->assertSee('Inmueble propio')
            ->assertSee(sprintf('CSC-%08d', $ownedProperty->id))
            ->assertDontSee('Inmueble ajeno');
    }

    public function test_admin_property_search_is_preserved_in_pagination_links(): void
    {
        $owner = User::factory()->create();

        Property::factory()->count(11)->create([
            'user_id' => $owner->id,
            'city' => 'Cartagena',
        ]);

        $response = $this->actingAs($this->createAdmin())
            ->get(route('dashboard.index', ['search' => 'Cartagena']));

        $response->assertOk()
            ->assertSee('search=Cartagena');
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
