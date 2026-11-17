<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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
        Gate::define('manage-schedules', fn (User $user): bool => $user->is_admin === true);
        Gate::define('view-own-schedule', fn (User $user): bool => ! $user->is_admin && $user->teacher_id !== null);

        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
            $minutes = config('auth.passwords.users.expire');

            return (new MailMessage)
                ->subject('Đặt lại mật khẩu HUIT Schedule Hub')
                ->greeting('Xin chào '.$user->name)
                ->line('Bạn đã yêu cầu đặt lại mật khẩu cho HUIT Schedule Hub.')
                ->action('Đặt lại mật khẩu', $url)
                ->line("Liên kết có hiệu lực trong $minutes phút và chỉ dùng được một lần.")
                ->line('Nếu bạn không yêu cầu, hãy bỏ qua email này.');
        });
    }
}
