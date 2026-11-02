<?php

namespace Tests\Feature;

use App\Mail\AdminPropertyRequest;
use App\Mail\AppraisalCompanyRequest;
use App\Mail\OwnerMediaRequest;
use App\Modules\Media\Models\Media;
use App\Modules\Property\Models\Property;
use App\Modules\Tours360\Models\Tour;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyAppraisalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_an_appraiser_and_assign_properties(): void
    {
        $admin = $this->createAdmin();
        $property = $this->createProperty(User::factory()->create());

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Avaluador asignado',
            'email' => 'appraiser@example.test',
            'role' => 'appraiser',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ])->assertRedirect(route('admin.users.index'));

        $appraiser = User::query()->where('email', 'appraiser@example.test')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.users.index', [
            'assignment_user_id' => $appraiser->id,
            'property_code' => $property->identification_code,
        ]))
            ->assertOk()
            ->assertSee($property->title)
            ->assertSee('Asignar propiedad');

        $this->actingAs($admin)->post(route('admin.users.properties.store', $appraiser), [
            'property_code' => $property->identification_code,
        ])->assertRedirect(route('admin.users.index', [
            'assignment_user_id' => $appraiser->id,
            'property_code' => $property->identification_code,
        ]));

        $this->assertDatabaseHas('users', [
            'id' => $appraiser->id,
            'role' => 'appraiser',
        ]);
        $this->assertDatabaseHas('appraiser_property', [
            'appraiser_id' => $appraiser->id,
            'property_id' => $property->id,
        ]);
    }

    public function test_assigned_appraiser_can_upload_an_appraisal_pdf_to_r2(): void
    {
        Storage::fake('r2');
        $appraiser = $this->createAppraiser();
        $property = $this->createProperty(User::factory()->create());
        $appraiser->assignedAppraisalProperties()->attach($property);

        $response = $this->actingAs($appraiser)->post(route('properties.appraisals.upload', $property), [
            'appraisal' => UploadedFile::fake()->createWithContent(
                'avaluo.pdf',
                "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF",
            ),
        ]);

        $response->assertRedirect(route('dashboard.index'));
        $media = Media::query()->where('property_id', $property->id)->where('type', 'appraisal')->firstOrFail();

        $this->assertStringStartsWith("Avaluos/{$property->id}/", $media->url_or_path);
        Storage::disk('r2')->assertExists($media->url_or_path);
    }

    public function test_uploading_a_new_appraisal_replaces_the_existing_pdf(): void
    {
        Storage::fake('r2');
        $appraiser = $this->createAppraiser();
        $property = $this->createProperty(User::factory()->create());
        $appraiser->assignedAppraisalProperties()->attach($property);
        $oldPath = "Avaluos/{$property->id}/old.pdf";
        Storage::disk('r2')->put($oldPath, "%PDF-1.4\n%%EOF");
        $property->media()->create([
            'type' => 'appraisal',
            'url_or_path' => $oldPath,
            'title' => 'Avalúo anterior',
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
        ]);

        $this->actingAs($appraiser)->post(route('properties.appraisals.upload', $property), [
            'appraisal' => UploadedFile::fake()->createWithContent(
                'avaluo-nuevo.pdf',
                "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF",
            ),
        ])->assertRedirect(route('dashboard.index'));

        $this->assertDatabaseCount('media', 1);
        $media = Media::query()->where('property_id', $property->id)->where('type', 'appraisal')->firstOrFail();
        $this->assertNotSame($oldPath, $media->url_or_path);
        Storage::disk('r2')->assertMissing($oldPath);
        Storage::disk('r2')->assertExists($media->url_or_path);
    }

    public function test_appraiser_cannot_upload_to_unassigned_properties_or_other_users_can_upload(): void
    {
        Storage::fake('r2');
        $appraiser = $this->createAppraiser();
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        $file = UploadedFile::fake()->createWithContent(
            'avaluo.pdf',
            "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF",
        );

        $this->actingAs($appraiser)
            ->post(route('properties.appraisals.upload', $property), ['appraisal' => $file])
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('properties.appraisals.upload', $property), ['appraisal' => $file])
            ->assertForbidden();

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_appraiser_dashboard_shows_only_assigned_properties_and_the_appraisal_upload_action(): void
    {
        $appraiser = $this->createAppraiser();
        $assignedProperty = $this->createProperty(User::factory()->create(), 'Inmueble asignado');
        $unassignedProperty = $this->createProperty(User::factory()->create(), 'Inmueble sin asignar');
        $appraiser->assignedAppraisalProperties()->attach($assignedProperty);

        $response = $this->actingAs($appraiser)->get(route('dashboard.index'));

        $response->assertOk()
            ->assertSee($assignedProperty->title)
            ->assertDontSee($unassignedProperty->title)
            ->assertSee(route('properties.appraisals.upload', $assignedProperty), false)
            ->assertDontSee(route('properties.edit', $assignedProperty), false)
            ->assertDontSee('Publicar inmueble');

        $this->get(route('properties.create'))->assertForbidden();
        $this->get(route('properties.edit', $assignedProperty))->assertForbidden();
        $this->post(route('properties.appraisals.request', $assignedProperty))->assertForbidden();

        $ownedProperty = $this->createProperty($appraiser, 'Inmueble propio del avaluador');
        $this->post(route('properties.requests.store', $ownedProperty), [
            'type' => 'verification',
        ])->assertForbidden();
    }

    public function test_appraisal_upload_rejects_non_pdf_files(): void
    {
        Storage::fake('r2');
        $appraiser = $this->createAppraiser();
        $property = $this->createProperty(User::factory()->create());
        $appraiser->assignedAppraisalProperties()->attach($property);

        $this->actingAs($appraiser)
            ->from(route('dashboard.index'))
            ->post(route('properties.appraisals.upload', $property), [
                'appraisal' => UploadedFile::fake()->createWithContent('avaluo.txt', 'no es un PDF'),
            ])
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHasErrors('appraisal');

        $this->assertDatabaseCount('media', 0);
        $this->assertSame([], Storage::disk('r2')->allFiles());
    }

    public function test_oversized_appraisal_upload_shows_the_pdf_size_limit(): void
    {
        $appraiser = $this->createAppraiser();
        $property = $this->createProperty(User::factory()->create());

        $this->actingAs($appraiser)
            ->from(route('dashboard.index'))
            ->call('POST', route('properties.appraisals.upload', $property, false), [], [], [], [
                'CONTENT_LENGTH' => (string) PHP_INT_MAX,
            ])
            ->assertRedirect(route('dashboard.index'))
            ->assertSessionHasErrors([
                'appraisal' => 'El avalúo comercial no puede superar los 20 MB.',
            ]);
    }

    public function test_property_page_embeds_the_uploaded_appraisal_pdf(): void
    {
        Storage::fake('r2');
        $property = $this->createProperty(User::factory()->create());
        $path = "Avaluos/{$property->id}/avaluo.pdf";
        Storage::disk('r2')->put($path, "%PDF-1.4\n%%EOF");
        $property->media()->create([
            'type' => 'appraisal',
            'url_or_path' => $path,
            'title' => 'Avalúo comercial',
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
        ]);

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee(route('properties.appraisals.show', $property), false)
            ->assertSee('Avalúo comercial');

        $this->get(route('properties.appraisals.show', $property))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="avaluo-comercial.pdf"');
    }

    public function test_owner_can_request_a_commercial_appraisal_from_the_configured_company(): void
    {
        config(['services.appraisals.address' => 'solicitudes@avaluos.example.test']);
        $owner = User::factory()->create([
            'name' => 'Propietaria de prueba',
            'email' => 'owner@example.test',
            'phone_number' => '+573001234567',
        ]);
        $property = $this->createProperty($owner);
        Mail::fake();

        $this->actingAs($owner)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee(route('properties.appraisals.request', $property), false)
            ->assertSee('Solicitar avalúo comercial');

        $this->actingAs($owner)
            ->post(route('properties.appraisals.request', $property))
            ->assertRedirect()
            ->assertSessionHas('success', 'La casa de avalúos recibió la solicitud y se pondrá en contacto con el propietario.');

        Mail::assertSent(AppraisalCompanyRequest::class, fn (AppraisalCompanyRequest $mail): bool => $mail->hasTo('solicitudes@avaluos.example.test')
            && $mail->property->is($property)
            && $mail->owner->is($owner)
            && str_contains(strip_tags($mail->render()), 'owner@example.test')
            && str_contains(strip_tags($mail->render()), '+573001234567')
        );
        Mail::assertNotSent(OwnerMediaRequest::class);
        $this->assertDatabaseCount('property_requests', 0);
    }

    public function test_only_the_property_owner_can_request_an_appraisal_and_missing_company_email_is_reported(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $this->actingAs($otherUser)
            ->post(route('properties.appraisals.request', $property))
            ->assertForbidden();

        config(['services.appraisals.address' => null]);

        $this->actingAs($owner)
            ->post(route('properties.appraisals.request', $property))
            ->assertRedirect()
            ->assertSessionHas('warning', 'No se pudo enviar la solicitud. Configura el correo de la casa de avalúos.');

        Mail::assertNothingOutgoing();
    }

    public function test_property_media_interest_is_one_email_to_the_owner_without_administrator_notification(): void
    {
        $this->createAdmin();
        $owner = User::factory()->create();
        $property = $this->createProperty($owner);
        Mail::fake();

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertSee('Solicitar al propietario adjuntar videos o recorrido 360°')
            ->assertDontSee('name="requester_name"', false);

        $this->postJson(route('properties.media-request', $property), [
            'media_type' => 'media_package',
        ])->assertOk()
            ->assertJsonPath('message', 'El propietario recibió un aviso sobre el interés en su inmueble.');

        Mail::assertSent(OwnerMediaRequest::class, fn (OwnerMediaRequest $mail): bool => $mail->hasTo($owner->email)
            && $mail->mediaType === 'media_package'
            && str_contains(strip_tags($mail->render()), 'Hay personas interesadas en tu propiedad')
            && str_contains(strip_tags($mail->render()), 'videos profesionales')
            && str_contains(strip_tags($mail->render()), 'recorridos virtuales 360°')
            && ! str_contains(strip_tags($mail->render()), 'planos')
            && str_contains(strip_tags($mail->render()), 'un 70% más de probabilidades')
        );
        Mail::assertNotSent(AppraisalCompanyRequest::class);
        Mail::assertNotSent(AdminPropertyRequest::class);
        $this->assertDatabaseCount('property_requests', 0);
    }

    public function test_property_media_request_button_is_hidden_when_video_and_tour_are_available(): void
    {
        $property = $this->createProperty(User::factory()->create());
        $property->media()->create([
            'type' => 'video',
            'url_or_path' => 'https://media.example.test/property-video.mp4',
            'title' => 'property-video.mp4',
            'mediable_type' => Property::class,
            'mediable_id' => $property->id,
        ]);
        $tour = Tour::factory()->create();
        $property->tour()->associate($tour)->save();

        $this->get(route('properties.show', $property))
            ->assertOk()
            ->assertDontSee('Solicitar al propietario adjuntar videos o recorrido 360°');
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        return $admin;
    }

    private function createAppraiser(): User
    {
        $appraiser = User::factory()->create();
        $appraiser->role = 'appraiser';
        $appraiser->save();

        return $appraiser;
    }

    private function createProperty(User $owner, string $title = 'Apartamento de prueba'): Property
    {
        return $owner->properties()->create([
            'title' => $title,
            'description' => 'Inmueble de prueba para avalúos.',
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
