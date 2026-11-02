<?php

namespace App\Modules\User\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetController extends Controller
{
    private const GENERIC_RESET_MESSAGE = 'Si existe una cuenta asociada a ese correo, recibirás instrucciones para restablecer la contraseña.';

    public function showLinkRequestForm(): View
    {
        return view('user::auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            Password::sendResetLink($validated);
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            Log::error('No fue posible enviar el correo para restablecer la contraseña.', [
                'transport_exception' => $exception::class,
            ]);
        }

        return back()->with('status', self::GENERIC_RESET_MESSAGE);
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('user::auth.reset-password', [
            'email' => $request->query('email'),
            'token' => $token,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Tu contraseña se restableció correctamente. Ya puedes iniciar sesión.');
        }

        $message = $status === Password::RESET_THROTTLED
            ? 'Espera un minuto antes de solicitar otro enlace.'
            : 'El enlace no es válido o expiró. Solicita uno nuevo para continuar.';

        return back()->withErrors(['email' => $message])->withInput($request->only('email'));
    }
}
