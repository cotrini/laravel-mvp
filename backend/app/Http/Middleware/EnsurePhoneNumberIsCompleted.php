<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneNumberIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()
            && $request->session()->get('phone_completion_required', false)
            && ! $request->routeIs('profile.phone.*', 'logout', 'firebase-auth', 'habeas-data.accept')
        ) {
            return redirect()->guest(route('profile.phone.show'));
        }

        return $next($request);
    }
}
