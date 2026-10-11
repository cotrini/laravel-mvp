<?php

namespace App\Modules\Property\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class PropertyRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Cargamos las rutas Web
        Route::middleware('web')
            ->group(app_path('Modules/Property/Routes/web.php'));

        // Cargamos las rutas API para Expo (con prefijo /api/v1)
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(app_path('Modules/Property/Routes/api.php'));
    }
}