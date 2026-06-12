<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Database\Eloquent\Events\Created;
use Illuminate\Database\Eloquent\Events\Updated;
use Illuminate\Database\Eloquent\Events\Deleted;
use Illuminate\Database\Eloquent\Events\Restored;
use Illuminate\Database\Eloquent\Events\ForceDeleted;
use App\Listeners\ModelEventAuditListener;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(Created::class, ModelEventAuditListener::class);
        Event::listen(Updated::class, ModelEventAuditListener::class);
        Event::listen(Deleted::class, ModelEventAuditListener::class);
        Event::listen(ForceDeleted::class, ModelEventAuditListener::class);
        Event::listen(Restored::class, ModelEventAuditListener::class);
    }

    public function register(): void
    {
        //
    }
}
