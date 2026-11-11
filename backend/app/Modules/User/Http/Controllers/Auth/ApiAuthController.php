<?php

namespace App\Modules\User\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Rules\ColombianPhoneNumber;
use App\Modules\User\Support\ColombianPhoneNumber as ColombianPhoneNumberFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

// Autenticación de la app móvil contra la base de datos (misma validación y reglas que la web).
class ApiAuthController extends Controller
{
    private const EMAIL_VERIFICATION_RESEND_LIMIT_SECONDS = 60;

    public function register(Request $request): JsonResponse
    {
        $normalizedPhoneNumber = ColombianPhoneNumberFormatter::normalize($request->input('phone_number'));

        if (is_string($normalizedPhoneNumber)) {
            $request->merge(['phone_number' => $normalizedPhoneNumber]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['required', 'string', new ColombianPhoneNumber, 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create(collect($validated)->only(['name', 'email', 'phone_number', 'password'])->all());
        $user->email_verification_required = true;
        $user->save();

        $emailSent = $this->sendVerificationNotification($user);

        return response()->json([
            'message' => $emailSent
                ? 'Te enviamos un enlace para verificar tu correo electrónico.'
                : 'Tu cuenta fue creada, pero no pudimos entregar el correo de verificación. Intenta reenviarlo en un momento.',
            'email_sent' => $emailSent,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::validate($credentials)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            ], 401);
        }

        $user = User::query()->where('email', $credentials['email'])->firstOrFail();

        if ($user->is_banned) {
            return response()->json(['message' => 'Esta cuenta está baneada y no puede iniciar sesión.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Verifica tu correo electrónico antes de iniciar sesión.',
                'code' => 'email_not_verified',
            ], 403);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        PersonalAccessToken::findToken((string) $request->bearerToken())?->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        // La respuesta es la misma exista o no la cuenta, para no revelar correos registrados.
        if ($user !== null && ! $user->hasVerifiedEmail() && ! RateLimiter::tooManyAttempts($this->emailVerificationRateLimitKey($user), 1)) {
            $this->sendVerificationNotification($user);
        }

        return response()->json([
            'message' => 'Si la cuenta existe y aún no está verificada, enviamos un nuevo enlace de verificación.',
        ]);
    }

    private function sendVerificationNotification(User $user): bool
    {
        RateLimiter::hit(
            $this->emailVerificationRateLimitKey($user),
            self::EMAIL_VERIFICATION_RESEND_LIMIT_SECONDS,
        );

        try {
            $user->sendEmailVerificationNotification();

            return true;
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            Log::error('No fue posible enviar el correo de verificación.', [
                'user_id' => $user->getKey(),
                'transport_exception' => $exception::class,
            ]);

            return false;
        }
    }

    private function emailVerificationRateLimitKey(User $user): string
    {
        return "email-verification-resend:{$user->getAuthIdentifier()}";
    }
}
