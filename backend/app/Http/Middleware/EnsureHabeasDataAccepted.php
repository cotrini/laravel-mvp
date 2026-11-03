<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHabeasDataAccepted
{
    /**
     * Bloquea cualquier acción de escritura (publicar, subir, pagar…) hasta aceptar el aviso de Habeas Data.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && ! $user->hasAcceptedHabeasData()
            && ! $request->isMethodSafe()
            && ! $request->routeIs('habeas-data.accept', 'logout', 'firebase-auth')
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Debes aceptar el aviso de tratamiento de datos personales para continuar.',
                ], 403);
            }

            return back()->withErrors([
                'habeas_data' => 'Debes aceptar el aviso de tratamiento de datos personales para continuar.',
            ]);
        }

        return $next($request);
    }
}
