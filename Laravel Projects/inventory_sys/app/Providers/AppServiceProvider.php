<?php

namespace App\Providers;

use App\Listeners\ModelEventAuditListener;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Events\Created;
use Illuminate\Database\Eloquent\Events\Updated;
use Illuminate\Database\Eloquent\Events\Deleted;
use Illuminate\Database\Eloquent\Events\Restored;
use Illuminate\Database\Eloquent\Events\ForceDeleted;
use Illuminate\Support\Facades\Event;
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
        // Register event listeners for audit/deletion logging
        Event::listen(Created::class, ModelEventAuditListener::class);
        Event::listen(Updated::class, ModelEventAuditListener::class);
        Event::listen(Deleted::class, ModelEventAuditListener::class);
        Event::listen(ForceDeleted::class, ModelEventAuditListener::class);
        Event::listen(Restored::class, ModelEventAuditListener::class);

        // Implicitly grant "super-admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Gate::define('access-admin-panel', function ($user) {
            return $user->hasRole('super-admin');
        });

        // Register policy for Spatie Role model (not auto-discovered)
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);
    }
}
