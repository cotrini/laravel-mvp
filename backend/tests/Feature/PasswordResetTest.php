<?php

namespace Tests\Feature;

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_links_to_the_password_recovery_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'));
    }

    public function test_recovery_and_reset_forms_are_available_to_guests(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recupera tu contraseña')
            ->assertSee(route('password.email'));

        $this->get(route('password.reset', [
            'token' => 'example-token',
            'email' => 'user@example.test',
        ]))
            ->assertOk()
            ->assertSee('Crea una nueva contraseña')
            ->assertSee(route('password.update'));
    }

    public function test_password_reset_request_sends_a_branded_email_with_a_reset_link(): void
    {
        $user = User::factory()->create([
            'name' => 'Cuenta <script>alert("xss")</script>',
        ]);
        Notification::fake([ResetPassword::class]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect()
            ->assertSessionHas(
                'status',
                'Si existe una cuenta asociada a ese correo, recibirás instrucciones para restablecer la contraseña.',
            );
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user): bool {
                $mailMessage = $notification->toMail($user);

                $this->assertSame(
                    'Restablece tu contraseña | Colombia Sin Comisión',
                    $mailMessage->subject,
                );
                $this->assertSame([
                    'html' => 'mail.reset-password',
                    'text' => 'mail.reset-password-text',
                ], $mailMessage->view);

                $html = view($mailMessage->view['html'], $mailMessage->viewData)->render();
                $text = view($mailMessage->view['text'], $mailMessage->viewData)->render();
                $resetUrl = route('password.reset', [
                    'token' => $notification->token,
                    'email' => $user->email,
                ]);

                $this->assertStringContainsString('Colombia', $html);
                $this->assertStringContainsString('Restablecer contraseña', $html);
                $this->assertStringContainsString($resetUrl, $html);
                $this->assertStringContainsString('&lt;script&gt;', $html);
                $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
                $this->assertStringContainsString($resetUrl, $text);

                return true;
            },
        );
    }

    public function test_unknown_email_receives_the_same_generic_response_without_a_notification(): void
    {
        Notification::fake([ResetPassword::class]);

        $response = $this->post(route('password.email'), [
            'email' => 'missing@example.test',
        ]);

        $response->assertRedirect()
            ->assertSessionHas(
                'status',
                'Si existe una cuenta asociada a ese correo, recibirás instrucciones para restablecer la contraseña.',
            );
        Notification::assertNothingSent();
    }

    public function test_a_recent_reset_request_is_throttled_without_changing_the_response(): void
    {
        $user = User::factory()->create();
        Notification::fake([ResetPassword::class]);
        $request = ['email' => $user->email];

        $this->post(route('password.email'), $request)
            ->assertSessionHas('status');
        $this->post(route('password.email'), $request)
            ->assertRedirect()
            ->assertSessionHas(
                'status',
                'Si existe una cuenta asociada a ese correo, recibirás instrucciones para restablecer la contraseña.',
            );

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_valid_reset_token_updates_the_password_and_is_consumed(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertSee('Crea una nueva contraseña');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'status',
                'Tu contraseña se restableció correctamente. Ya puedes iniciar sesión.',
            );

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_reset_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_reset_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
