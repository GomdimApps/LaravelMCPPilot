<?php

namespace GomdimApps\LaravelMCPPilot;

use Illuminate\Support\ServiceProvider;
use GomdimApps\LaravelMCPPilot\Database\DatabaseServiceProvider;
use GomdimApps\LaravelMCPPilot\Search\SearchServiceProvider;

class LaravelMCPPilotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-mcp-pilot.php', 'laravel-mcp-pilot');

        // Each tool ships its own ServiceProvider — adding a new tool means writing one more
        // provider class and registering it here, without touching this file otherwise.
        $this->app->register(SearchServiceProvider::class);
        $this->app->register(DatabaseServiceProvider::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/laravel-mcp-pilot.php' => config_path('laravel-mcp-pilot.php')], 'laravel-mcp-pilot-config');
    }
}
