<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
