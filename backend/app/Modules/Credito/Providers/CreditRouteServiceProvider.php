<?php

namespace App\Modules\Credito\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CreditRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->group(app_path('Modules/Credito/Routes/web.php'));

        View::addNamespace('credito', app_path('Modules/Credito/Resources/Views'));
    }
}
