<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Intervention\Image\Exceptions\MissingDependencyException;
use Tests\TestCase;

class PostTooLargeHandlingTest extends TestCase
{
    private const OVERSIZED_MESSAGE = 'Los archivos adjuntos superan el tamaño permitido para una sola solicitud. Reduce el tamaño de las fotografías (máximo 15 MB cada una) o carga menos archivos e inténtalo de nuevo.';

    public function test_oversized_form_request_redirects_back_with_a_friendly_error(): void
    {
        $owner = new User;
        $owner->email_verified_at = now();
        $this->actingAs($owner);

        $response = $this->from('/properties/43/edit')
            ->call('PUT', '/properties/43', [], [], [], [
                'CONTENT_LENGTH' => (string) PHP_INT_MAX,
            ]);

        $response->assertRedirect('/properties/43/edit')
            ->assertSessionHasErrors([
                'images' => self::OVERSIZED_MESSAGE,
            ]);
    }

    public function test_oversized_tour_request_redirects_back_with_a_friendly_error(): void
    {
        $owner = new User;
        $owner->email_verified_at = now();
        $this->actingAs($owner);

        $response = $this->from('/tours/create')
            ->call('POST', '/tours', [], [], [], [
                'CONTENT_LENGTH' => (string) PHP_INT_MAX,
            ]);

        $response->assertRedirect('/tours/create')
            ->assertSessionHasErrors([
                'scenes' => self::OVERSIZED_MESSAGE,
            ]);
    }

    public function test_oversized_json_request_returns_a_413_response(): void
    {
        Route::middleware('api')->post('/__tests/post-too-large-json', fn () => response()->noContent());

        $response = $this->call('POST', '/__tests/post-too-large-json', [], [], [], [
            'CONTENT_LENGTH' => (string) PHP_INT_MAX,
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response->assertStatus(413)
            ->assertJsonPath(
                'errors.files.0',
                self::OVERSIZED_MESSAGE,
            );
    }

    public function test_missing_gd_dependency_returns_a_friendly_json_error_for_scene_uploads(): void
    {
        Route::middleware('web')->post('/__tests/tours/scene-images', function (): never {
            throw new MissingDependencyException('GD PHP extension must be installed to use this driver');
        })->name('tours360.scene-images.store');

        $response = $this->post('/__tests/tours/scene-images', [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath(
                'errors.image.0',
                'El servidor no tiene habilitada la extensión GD necesaria para procesar fotografías. Reinicia el servidor PHP después de habilitarla.',
            );
    }

    public function test_missing_gd_dependency_redirects_back_with_a_friendly_form_error(): void
    {
        Route::middleware('web')->post('/__tests/tours/scene-images', function (): never {
            throw new MissingDependencyException('GD PHP extension must be installed to use this driver');
        })->name('tours360.scene-images.store');

        $response = $this->from('/tours/create')
            ->post('/__tests/tours/scene-images');

        $response->assertRedirect('/tours/create')
            ->assertSessionHasErrors([
                'image' => 'El servidor no tiene habilitada la extensión GD necesaria para procesar fotografías. Reinicia el servidor PHP después de habilitarla.',
            ]);
    }
}
