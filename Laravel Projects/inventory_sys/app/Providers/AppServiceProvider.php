<?php

namespace App\Providers;

use App\Listeners\ModelEventAuditListener;
use Illuminate\Database\Eloquent\Model;
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
        // Fix for Windows: ensure SYSTEMROOT is available to child processes (like mysqldump).
        // Without this, Windows Winsock cannot initialize in subprocesses → error 10106.
        if (PHP_OS_FAMILY === 'Windows' && ! getenv('SYSTEMROOT')) {
            putenv('SYSTEMROOT=C:\\Windows');
            $_ENV['SYSTEMROOT'] = 'C:\\Windows';
        }

        $auditListener = app(ModelEventAuditListener::class);

        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            Event::listen("eloquent.{$event}: *", function (string $eventName, array $payload) use ($auditListener, $event): void {
                $model = $payload[0] ?? null;

                if (! $model instanceof Model) {
                    return;
                }

                $auditListener->{'handle'.ucfirst($event)}($model);
            });
        }

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
