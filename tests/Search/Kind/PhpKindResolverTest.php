<?php

use App\Console\Commands\ThingSummaryCommand;
use App\Contracts\Exportable;
use App\Enums\ThingStatus;
use App\Events\ThingCreated;
use App\Http\Middleware\EnsureThingIsActive;
use App\Jobs\ProcessThing;
use App\Listeners\NotifyThingCreated;
use App\Models\Thing;
use App\Policies\ThingPolicy;
use App\Providers\ThingServiceProvider;
use App\Services\ThingExportService;
use GomdimApps\LaravelMCPPilot\Search\Kind\CommandKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ControllerKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\EventKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\JobKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ListenerKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\MiddlewareKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ModelKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\NativeTypeKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\PolicyKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ProviderKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ServiceKind;
use App\Concerns\HasDisplayName;

it('detects interfaces, traits, and enums via plain reflection', function () {
    $detector = new NativeTypeKind;

    expect($detector->kind(new ReflectionClass(Exportable::class)))->toBe('interface')
        ->and($detector->kind(new ReflectionClass(HasDisplayName::class)))->toBe('trait')
        ->and($detector->kind(new ReflectionClass(ThingStatus::class)))->toBe('enum');
});

it('detects a command and reads its signature from the default property', function () {
    $detector = new CommandKind;

    expect($detector->supports(new ReflectionClass(ThingSummaryCommand::class)))->toBeTrue()
        ->and($detector->context(new ReflectionClass(ThingSummaryCommand::class)))->toBe(['signature' => 'thing:summary']);
});

it('falls back to the controllers_namespace convention when the fixture does not extend the base Controller', function () {
    $detector = new ControllerKind('App\\Http\\Controllers\\');

    expect($detector->supports(new ReflectionClass(\App\Http\Controllers\ThingController::class)))->toBeTrue();
});

it('detects a middleware via Route::aliasMiddleware and reports its alias', function () {
    $detector = new MiddlewareKind('App\\Http\\Middleware\\');
    $reflection = new ReflectionClass(EnsureThingIsActive::class);

    expect($detector->supports($reflection))->toBeTrue()
        ->and($detector->context($reflection)['aliases'])->toContain('thing.active');
});

it('detects a registered provider', function () {
    $detector = new ProviderKind;
    $reflection = new ReflectionClass(ThingServiceProvider::class);

    expect($detector->supports($reflection))->toBeTrue()
        ->and($detector->context($reflection))->toBe(['registered' => true]);
});

it('detects a queueable job', function () {
    expect((new JobKind)->supports(new ReflectionClass(ProcessThing::class)))->toBeTrue();
});

it('detects a policy via Gate::policy and reports which model it belongs to', function () {
    $detector = new PolicyKind('App\\Policies\\');
    $reflection = new ReflectionClass(ThingPolicy::class);

    expect($detector->supports($reflection))->toBeTrue()
        ->and($detector->context($reflection)['policy_for'])->toContain(Thing::class);
});

it('detects a listener via Event::listen and reports which event it listens to', function () {
    $detector = new ListenerKind('App\\Listeners\\');
    $reflection = new ReflectionClass(NotifyThingCreated::class);

    expect($detector->supports($reflection))->toBeTrue()
        ->and($detector->context($reflection)['listens_to'])->toContain(ThingCreated::class);
});

it('detects an Eloquent model', function () {
    expect((new ModelKind)->supports(new ReflectionClass(Thing::class)))->toBeTrue();
});

it('detects a namespace-convention event', function () {
    expect((new EventKind('App\\Events\\'))->supports(new ReflectionClass(ThingCreated::class)))->toBeTrue();
});

it('falls back to a generic service bucket for a namespace-convention service class', function () {
    expect((new ServiceKind('App\\Services\\'))->supports(new ReflectionClass(ThingExportService::class)))->toBeTrue();
});
