<?php

namespace App\Http\Middleware;

use App\Modules\User\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Exception\InvalidArgumentException as FirebaseInvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFirebaseToken
{
    public function __construct(private readonly FirebaseAuthContract $firebaseAuth) {}

    public function handle(Request $request, Closure $next): Response
    {
        $idToken = $request->bearerToken();

        if (! is_string($idToken) || $idToken === '') {
            return $this->unauthenticated();
        }

        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken|FirebaseInvalidArgumentException|RevokedIdToken) {
            return $this->unauthenticated();
        }

        $claims = $verifiedToken->claims();
        $email = $claims->get('email');

        if (
            $claims->get('email_verified') !== true
            || ! is_string($email)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
        ) {
            return $this->unauthenticated();
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return response()->json(['message' => 'La cuenta no está registrada.'], 401);
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'Esta cuenta no puede acceder a la aplicación.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Verifica el correo electrónico de tu cuenta.'], 403);
        }

        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => 'Se requiere un Firebase ID token válido.'], 401);
    }
}
