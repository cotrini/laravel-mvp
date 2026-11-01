<?php

use App\Modules\Credito\Providers\CreditRouteServiceProvider;
use App\Modules\Dashboard\Providers\DashboardRouteServiceProvider;
use App\Modules\Media\Providers\MediaRouteServiceProvider;
use App\Modules\Property\Providers\PropertyRouteServiceProvider;
use App\Modules\Tours360\Providers\Tour360RouteServiceProvider;
use App\Modules\User\Providers\UserRouteServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    PropertyRouteServiceProvider::class,
    MediaRouteServiceProvider::class,
    UserRouteServiceProvider::class,
    DashboardRouteServiceProvider::class,
    Tour360RouteServiceProvider::class,
    CreditRouteServiceProvider::class,
];
