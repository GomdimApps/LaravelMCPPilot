<?php

namespace GomdimApps\LaravelMCPPilot;

use Illuminate\Support\ServiceProvider;
use GomdimApps\LaravelMCPPilot\Database\DatabaseServiceProvider;
use GomdimApps\LaravelMCPPilot\Search\SearchServiceProvider;

class LaravelMCPPilotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfig();

        if (! config('laravel-mcp-pilot.enabled')) {
            return;
        }

        $this->app->addDeferredServices(array_fill_keys(
            (new SearchServiceProvider($this->app))->provides(),
            SearchServiceProvider::class,
        ));

        $this->app->addDeferredServices(array_fill_keys(
            (new DatabaseServiceProvider($this->app))->provides(),
            DatabaseServiceProvider::class,
        ));
    }

    /**
     * A deep merge, not mergeConfigFrom(): that helper only merges top-level keys ('search',
     * 'database'), so a host app that published this file before a newer package version added
     * a new nested key (e.g. search.middleware_namespace) would keep its stale, now-incomplete
     * 'search' array wholesale — silently losing the new default and passing null into whatever
     * reads it. array_replace_recursive() keeps any key the host DID override, at any depth, and
     * still falls back to the package default for everything it didn't.
     */
    private function mergeConfig(): void
    {
        $config = $this->app['config'];

        $config->set('laravel-mcp-pilot', array_replace_recursive(
            require __DIR__.'/../config/laravel-mcp-pilot.php',
            $config->get('laravel-mcp-pilot', []),
        ));
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/laravel-mcp-pilot.php' => config_path('laravel-mcp-pilot.php')], 'laravel-mcp-pilot-config');
    }
}
