<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $resetPath = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false);
            $resetUrl = rtrim((string) config('app.url'), '/') . $resetPath;

            return (new MailMessage)
                ->subject('Reset Password Akun SAYAKA GROUP')
                ->greeting('Halo!')
                ->line('Kami menerima permintaan reset password untuk akun SAYAKA GROUP Anda.')
                ->action('Reset Password', $resetUrl)
                ->line('Link reset password ini berlaku selama ' . config('auth.passwords.users.expire') . ' menit.')
                ->line('Jika Anda tidak meminta reset password, abaikan email ini.')
                ->salutation('Salam, SAYAKA GROUP');
        });
    }
}
