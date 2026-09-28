<?php

namespace GomdimApps\LaravelMCPPilot\Tests;

use GomdimApps\LaravelMCPPilot\LaravelMCPPilotServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelMCPPilotServiceProvider::class,
            PermissionServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // The fixture tree lives outside Testbench's skeleton base_path(), so
        // FileScanner::relativePath() falls back to returning the full absolute path for every
        // fixture file (Str::after()'s no-op case) — assertions below match on that accordingly.
        $fixtures = __DIR__.'/Fixtures';

        $app['config']->set('laravel-mcp-pilot.search.database_path', ':memory:');
        $app['config']->set('laravel-mcp-pilot.search.php.root', $fixtures.'/app');
        $app['config']->set('laravel-mcp-pilot.search.php.namespace', 'App');
        $app['config']->set('laravel-mcp-pilot.search.controllers_namespace', 'App\\Http\\Controllers\\');
        $app['config']->set('laravel-mcp-pilot.search.frontend_root', $fixtures.'/resources/js');
        $app['config']->set('laravel-mcp-pilot.search.core_paths', [$fixtures.'/bootstrap', $fixtures.'/public']);
        $app['config']->set('laravel-mcp-pilot.search.support_paths', [
            'view' => $fixtures.'/resources/views',
            'lang' => $fixtures.'/lang',
            'config' => $fixtures.'/config',
            'migration' => $fixtures.'/database/migrations',
            'factory' => $fixtures.'/database/factories',
            'seeder' => $fixtures.'/database/seeders',
            'test' => $fixtures.'/no-such-tests-dir',
            'doc' => $fixtures.'/docs',
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
