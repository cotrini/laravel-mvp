<?php

namespace App\Providers;

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;
use Kreait\Firebase\Contract\Auth as FirebaseAuthContract;
use Kreait\Firebase\Factory;

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
        $this->app->singleton(
            FirebaseAuthContract::class,
            fn (): FirebaseAuthContract => (new Factory)
                ->withServiceAccount(storage_path('app/firebase-credentials.json'))
                ->createAuth(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Confirma tu correo electrónico | Colombia Sin Comisión')
                ->view([
                    'html' => 'mail.verify-email',
                    'text' => 'mail.verify-email-text',
                ], [
                    'userName' => $notifiable->name,
                    'verificationUrl' => $url,
                    'expirationMinutes' => config('auth.verification.expire', 60),
                ]);
        });

        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Restablece tu contraseña | Colombia Sin Comisión')
                ->view([
                    'html' => 'mail.reset-password',
                    'text' => 'mail.reset-password-text',
                ], [
                    'userName' => $user->name,
                    'resetUrl' => $resetUrl,
                    'expirationMinutes' => config(
                        'auth.passwords.'.config('auth.defaults.passwords').'.expire',
                        60,
                    ),
                ]);
        });

        Gate::define('manage-users', function (User $user): bool {
            return $user->isAdmin();
        });

        Gate::define('manage-location-catalog', function (User $user): bool {
            return $user->isAdmin() || in_array(
                mb_strtolower($user->email),
                config('location.admin_emails', []),
                true,
            );
        });

        foreach (File::directories(app_path('Modules')) as $modulePath) {
            $migrationPath = $modulePath.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'Migrations';

            if (File::isDirectory($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }
    }
}
