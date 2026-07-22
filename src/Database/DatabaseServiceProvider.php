<?php

namespace GomdimApps\LaravelMCPPilot\Database;

use Illuminate\Support\ServiceProvider;
use GomdimApps\LaravelMCPPilot\Database\Support\KeywordSearch;
use GomdimApps\LaravelMCPPilot\Database\Support\SpatiePermissionIntrospector;
use GomdimApps\LaravelMCPPilot\Database\Support\SqlStatementGuard;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SqlStatementGuard::class, fn () => new SqlStatementGuard(
            config('laravel-mcp-pilot.database.max_rows'),
        ));

        $this->app->singleton(KeywordSearch::class, fn () => new KeywordSearch());

        $this->app->singleton(SpatiePermissionIntrospector::class, fn () => new SpatiePermissionIntrospector(
            config('laravel-mcp-pilot.database.spatie_permission.enabled'),
        ));

        $this->app->singleton(DatabaseIntrospectionService::class, fn ($app) => new DatabaseIntrospectionService(
            $app->make(SqlStatementGuard::class),
            $app->make(KeywordSearch::class),
            $app->make(SpatiePermissionIntrospector::class),
            (bool) config('laravel-mcp-pilot.database.allow_write_queries'),
        ));
    }
}
