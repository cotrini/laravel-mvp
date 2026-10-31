<?php

namespace App\Modules\User\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;
use Illuminate\Support\Str;

class FirebaseAuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $idToken = $request->input('idToken');

        try {
            // Inicializar Firebase Admin SDK con el archivo JSON descargado
            $firebase = (new Factory)
                ->withServiceAccount(storage_path('app/firebase-credentials.json'));
            
            $auth = $firebase->createAuth();
            
            // Verificar el token recibido del frontend
            $verifiedIdToken = $auth->verifyIdToken($idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            
            // Obtener información del usuario autenticado en Firebase
            $firebaseUser = $auth->getUser($uid);
            $email = $firebaseUser->email;
            $name = $firebaseUser->displayName ?? 'Usuario';

            // Buscar o crear el usuario en MySQL
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'firebase_uid' => $uid,
                    'password' => bcrypt(Str::random(16)) // Contraseña aleatoria ya que usan login social
                ]
            );

            // Iniciar sesión en Laravel
            Auth::login($user);

            return response()->json([
                'status' => 'success',
                'redirect' => route('dashboard.index') // O la ruta a donde quieras enviarlos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Autenticación fallida: ' . $e->getMessage()
            ], 401);
        }
    }
}