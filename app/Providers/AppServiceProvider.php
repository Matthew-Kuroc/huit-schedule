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
        Gate::define('manage-schedules', fn (User $user): bool => $user->is_admin === true);
        Gate::define('view-own-schedule', fn (User $user): bool => ! $user->is_admin && $user->teacher_id !== null);
    }
}
