<?php

namespace App\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Rules\ColombianPhoneNumber;
use App\Modules\User\Support\ColombianPhoneNumber as ColombianPhoneNumberFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\Auth\RevokedIdToken;
use Kreait\Firebase\Exception\InvalidArgumentException as FirebaseInvalidArgumentException;

class UserApiController extends Controller
{
    public function authenticate(Request $request, FirebaseAuthContract $firebaseAuth): JsonResponse
    {
        $idToken = $request->bearerToken();

        if (! is_string($idToken) || $idToken === '') {
            return response()->json(['message' => 'Envía el Firebase ID token en el encabezado Bearer.'], 401);
        }

        try {
            $verifiedToken = $firebaseAuth->verifyIdToken($idToken);
        } catch (FailedToVerifyToken|FirebaseInvalidArgumentException|RevokedIdToken) {
            return response()->json(['message' => 'El Firebase ID token no es válido o expiró.'], 401);
        }

        $claims = $verifiedToken->claims();
        $email = $claims->get('email');

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return response()->json(['message' => 'El token debe pertenecer a una cuenta con correo electrónico.'], 422);
        }

        if ($claims->get('email_verified') !== true) {
            return response()->json(['message' => 'Verifica el correo electrónico de tu cuenta de Firebase.'], 403);
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        if ($user->is_banned) {
            return response()->json(['message' => 'Esta cuenta no puede acceder a la aplicación.'], 403);
        }

        $name = $claims->get('name');
        $user->name = is_string($name) && filled($name)
            ? $name
            : Str::before($email, '@');

        if (! $user->exists) {
            $user->password = Str::random(40);
        }

        $user->email_verification_required = false;
        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'data' => $this->userData($user),
            'needs_phone_completion' => blank($user->phone_number),
        ], $user->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userData($request->user())]);
    }

    public function completePhone(Request $request): JsonResponse
    {
        $normalizedPhoneNumber = ColombianPhoneNumberFormatter::normalize($request->input('phone_number'));

        if (is_string($normalizedPhoneNumber)) {
            $request->merge(['phone_number' => $normalizedPhoneNumber]);
        }

        $user = $request->user();
        $validated = $request->validate([
            'phone_number' => [
                'required',
                'string',
                new ColombianPhoneNumber,
                Rule::unique('users', 'phone_number')->ignore($user),
            ],
        ]);

        $user->phone_number = $validated['phone_number'];
        $user->save();

        return response()->json([
            'data' => $this->userData($user->refresh()),
            'needs_phone_completion' => false,
        ]);
    }

    /**
     * @return array{id: int, name: string, email: string, phone_number: ?string, role: string, email_verified_at: ?string}
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
        ];
    }
}
