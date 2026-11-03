<?php

namespace App\Modules\Help\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class HelpRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->group(app_path('Modules/Help/Routes/web.php'));

        View::addNamespace('help', app_path('Modules/Help/Resources/Views'));
    }
}
