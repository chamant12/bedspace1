<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
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
        // Platform admins pass every policy check
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        // The admin template is Bootstrap 5
        Paginator::useBootstrapFive();
    }
}
