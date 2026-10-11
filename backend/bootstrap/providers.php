<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Modules\Property\Providers\PropertyRouteServiceProvider::class,
    App\Modules\Media\Providers\MediaRouteServiceProvider::class,
];
