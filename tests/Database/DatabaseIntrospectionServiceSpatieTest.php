<?php

use GomdimApps\LaravelMCPPilot\Database\DatabaseIntrospectionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $stub = dirname(__DIR__, 2).'/vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
    (include $stub)->up();

    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $permission = Permission::create(['name' => 'edit articles', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
});

it('adds a spatie_map to describeTable() for the roles table, resolved via Eloquent relationships', function () {
    $description = $this->app->make(DatabaseIntrospectionService::class)->describeTable('testing', 'roles');

    expect($description)->toHaveKey('spatie_map')
        ->and($description['spatie_map']['roles'])->toHaveCount(1)
        ->and($description['spatie_map']['roles'][0]['name'])->toBe('editor')
        ->and($description['spatie_map']['roles'][0]['guard_name'])->toBe('web')
        ->and($description['spatie_map']['roles'][0]['permissions'])->toBe(['edit articles']);
});

it('adds a spatie_map to describeTable() for the permissions table', function () {
    $description = $this->app->make(DatabaseIntrospectionService::class)->describeTable('testing', 'permissions');

    expect($description['spatie_map']['permissions'][0]['name'])->toBe('edit articles')
        ->and($description['spatie_map']['permissions'][0]['roles'])->toBe(['editor']);
});

it('does not add a spatie_map for a table unrelated to permissions', function () {
    Schema::create('widgets', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });

    $description = $this->app->make(DatabaseIntrospectionService::class)->describeTable('testing', 'widgets');

    expect($description)->not->toHaveKey('spatie_map');
});

it('does not add a spatie_map when the integration is disabled via config', function () {
    config(['laravel-mcp-pilot.database.spatie_permission.enabled' => false]);

    $description = $this->app->make(DatabaseIntrospectionService::class)->describeTable('testing', 'roles');

    expect($description)->not->toHaveKey('spatie_map');
});
