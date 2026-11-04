<?php

namespace App\Modules\Core\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CoreRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/v1')
            ->name('api.')
            ->middleware('api')
            ->group(app_path('Modules/Core/Routes/api.php'));
    }
}
