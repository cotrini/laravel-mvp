<?php

namespace App\Modules\Media\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\View;

class MediaRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Cargamos las rutas Web
        Route::middleware('web')
            ->group(app_path('Modules/Media/Routes/web.php'));

        // Cargamos las rutas API para Expo (con prefijo /api/v1)
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(app_path('Modules/Media/Routes/api.php'));

        //load the views for the module
        View::addNamespace('media', app_path('Modules/Media/Resources/Views'));
    }
}