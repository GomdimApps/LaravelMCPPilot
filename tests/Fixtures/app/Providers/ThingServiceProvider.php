<?php

namespace App\Providers;

use App\Console\Commands\ThingSummaryCommand;
use App\Events\ThingCreated;
use App\Http\Middleware\EnsureThingIsActive;
use App\Listeners\NotifyThingCreated;
use App\Models\Thing;
use App\Policies\ThingPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ThingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::aliasMiddleware('thing.active', EnsureThingIsActive::class);
        Gate::policy(Thing::class, ThingPolicy::class);
        Event::listen(ThingCreated::class, NotifyThingCreated::class);

        $this->commands([ThingSummaryCommand::class]);
    }
}
