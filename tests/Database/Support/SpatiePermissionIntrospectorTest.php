<?php

use GomdimApps\LaravelMCPPilot\Database\Support\SpatiePermissionIntrospector;

it('reports available when spatie/laravel-permission is installed and enabled', function () {
    expect((new SpatiePermissionIntrospector(enabled: true))->isAvailable())->toBeTrue();
});

it('reports unavailable when disabled via config, even though the package is installed', function () {
    expect((new SpatiePermissionIntrospector(enabled: false))->isAvailable())->toBeFalse();
});

it('returns null for a table that is not part of the spatie permission schema', function () {
    $introspector = new SpatiePermissionIntrospector(enabled: true);

    expect($introspector->map('testing', 'widgets'))->toBeNull();
});

it('returns null for a spatie table when disabled', function () {
    $introspector = new SpatiePermissionIntrospector(enabled: false);

    expect($introspector->map('testing', 'roles'))->toBeNull();
});
