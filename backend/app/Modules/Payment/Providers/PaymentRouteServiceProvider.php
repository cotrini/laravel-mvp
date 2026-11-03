<?php

namespace App\Modules\Payment\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class PaymentRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')
            ->group(app_path('Modules/Payment/Routes/web.php'));

        // El webhook usa el grupo api: sin sesión ni CSRF, autenticado por la firma de Wompi.
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(app_path('Modules/Payment/Routes/api.php'));

        View::addNamespace('payments', app_path('Modules/Payment/Resources/Views'));
    }
}
