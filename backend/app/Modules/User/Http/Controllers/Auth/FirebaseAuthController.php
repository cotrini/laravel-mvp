<?php

namespace App\Modules\User\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;

class FirebaseAuthController extends Controller
{
    public function authenticate(Request $request, FirebaseAuthContract $auth): JsonResponse
    {
        $idToken = $request->input('idToken');

        try {
            $verifiedIdToken = $auth->verifyIdToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub');

            $firebaseUser = $auth->getUser($uid);
            $email = $firebaseUser->email;
            $name = $firebaseUser->displayName ?? 'Usuario';

            $user = User::query()->firstOrNew(['email' => $email]);
            $user->name = $name;

            if (! $user->exists) {
                $user->password = Str::random(32);
            }

            $user->email_verification_required = false;
            $user->email_verified_at = now();
            $user->save();

            Auth::login($user);
            $request->session()->regenerate();

            if (blank($user->phone_number)) {
                $request->session()->put('phone_completion_required', true);
            } else {
                $request->session()->forget('phone_completion_required');
            }

            return response()->json([
                'status' => 'success',
                'redirect' => blank($user->phone_number)
                    ? route('profile.phone.show')
                    : route('dashboard.index'),
            ]);

        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Autenticación fallida: '.$exception->getMessage(),
            ], 401);
        }
    }
}
