<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ImageManagerInterface::class,
            fn (): ImageManagerInterface => ImageManager::usingDriver(GdDriver::class),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-location-catalog', function (User $user): bool {
            return in_array(
                mb_strtolower($user->email),
                config('location.admin_emails', []),
                true,
            );
        });

        $this->loadMigrationsFrom([
            database_path('migrations'),
            app_path('Modules/Property/Database/Migrations'),
            app_path('Modules/Media/Database/Migrations'),
            app_path('Modules/Tours360/Database/Migrations'),
        ]);
    }
}
