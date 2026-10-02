<?php

use GomdimApps\LaravelMCPPilot\Database\DatabaseIntrospectionService;
use GomdimApps\LaravelMCPPilot\LaravelMCPPilotServiceProvider;
use GomdimApps\LaravelMCPPilot\Search\SearchService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;

/**
 * Deliberately exercised against a bare Application, not $this->app from the Testbench TestCase:
 * Orchestra Testbench always bootstraps the console kernel for every test (so $this->artisan()
 * works), and Illuminate\Foundation\Console\Kernel::bootstrap() unconditionally calls
 * Application::loadDeferredProviders() — which force-registers every remaining deferred service
 * and then wipes the deferred-services table clean. That neutralizes DeferrableProvider inside
 * any Testbench test, even though it works as intended in a real app (a normal HTTP request
 * never boots the console kernel). A bare Application with no kernel involved is what actually
 * lets this behavior be observed.
 */
beforeEach(function () {
    $this->previousContainer = Container::getInstance();
});

afterEach(function () {
    Container::setInstance($this->previousContainer);
});

function bareAppWithPilotEnabled(bool $enabled): Application
{
    $app = new Application();
    $app->instance('config', new Repository(['laravel-mcp-pilot' => ['enabled' => $enabled]]));
    Container::setInstance($app);

    return $app;
}

it('defers SearchServiceProvider and DatabaseServiceProvider when enabled', function () {
    $app = bareAppWithPilotEnabled(enabled: true);

    (new LaravelMCPPilotServiceProvider($app))->register();

    expect($app->isDeferredService(SearchService::class))->toBeTrue()
        ->and($app->isDeferredService(DatabaseIntrospectionService::class))->toBeTrue();
});

it('does not wire any deferred services when laravel-mcp-pilot.enabled is false', function () {
    $app = bareAppWithPilotEnabled(enabled: false);

    (new LaravelMCPPilotServiceProvider($app))->register();

    expect($app->isDeferredService(SearchService::class))->toBeFalse()
        ->and($app->isDeferredService(DatabaseIntrospectionService::class))->toBeFalse();

    // SearchService itself would happen to auto-wire with an empty $indexers Collection even
    // with nothing registered (Laravel's container can build any class whose dependencies are
    // all either auto-wireable or have defaults) — DatabaseIntrospectionService's trailing
    // `bool $allowWriteQueries` (no default, no binding) can't, so it's the reliable proof that
    // nothing from this package was wired at all.
    expect(fn () => $app->make(DatabaseIntrospectionService::class))->toThrow(BindingResolutionException::class);
});
