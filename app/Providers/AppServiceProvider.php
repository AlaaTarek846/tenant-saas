<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontendUrl = rtrim(config('app.url'), '/');

            return $frontendUrl.'/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $frontendUrl = rtrim(config('app.url'), '/');
            $url = $frontendUrl.'/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
            $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject('إعادة تعيين كلمة المرور')
                ->greeting('مرحباً!')
                ->line('تلقّينا طلباً لإعادة تعيين كلمة مرور حسابك.')
                ->action('إعادة تعيين كلمة المرور', $url)
                ->line("ينتهي صلاحية هذا الرابط خلال {$expireMinutes} دقيقة.")
                ->line('إذا لم تطلب إعادة التعيين، تجاهل هذه الرسالة.');
        });
    }
}
