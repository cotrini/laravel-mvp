<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom([
        database_path('migrations'),
        app_path('Modules/Property/Database/Migrations'),
        app_path('Modules/Media/Database/Migrations'),
        app_path('Modules/Tours360/Database/Migrations'),
    ]);
    }
}
