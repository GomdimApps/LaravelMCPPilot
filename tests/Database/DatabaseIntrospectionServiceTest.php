<?php

use GomdimApps\LaravelMCPPilot\Database\DatabaseIntrospectionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('widgets', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('amount')->default(0);
    });

    DB::table('widgets')->insert([
        ['name' => 'Alpha', 'amount' => 10],
        ['name' => 'Beta', 'amount' => 20],
    ]);
});

it('lists tables with their row count', function () {
    $tables = collect($this->app->make(DatabaseIntrospectionService::class)->listTables('testing'))->keyBy('name');

    expect($tables->has('widgets'))->toBeTrue()
        ->and($tables['widgets']['row_count'])->toBe(2);
});

it('filters listed tables by keyword', function () {
    $service = $this->app->make(DatabaseIntrospectionService::class);

    expect(collect($service->listTables('testing', 'widg'))->pluck('name'))->toContain('widgets')
        ->and($service->listTables('testing', 'nonexistentzzz'))->toBeEmpty();
});

it('describes a table with columns, indexes, and foreign keys, with no spatie_map for an unrelated table', function () {
    $description = $this->app->make(DatabaseIntrospectionService::class)->describeTable('testing', 'widgets');

    expect($description['table'])->toBe('widgets')
        ->and(collect($description['columns'])->pluck('name'))->toContain('id', 'name', 'amount')
        ->and($description)->toHaveKeys(['columns', 'indexes', 'foreign_keys'])
        ->and($description)->not->toHaveKey('spatie_map');
});

it('runs a select query and reports the row count', function () {
    $result = $this->app->make(DatabaseIntrospectionService::class)->runQuery('testing', 'SELECT * FROM widgets');

    expect($result['count'])->toBe(2)
        ->and($result['truncated'])->toBeFalse()
        ->and(collect($result['rows'])->pluck('name'))->toContain('Alpha', 'Beta');
});

it('rejects a query containing more than one statement', function () {
    $this->app->make(DatabaseIntrospectionService::class)->runQuery('testing', 'SELECT * FROM widgets; DROP TABLE widgets');
})->throws(InvalidArgumentException::class, 'Only a single SQL statement is allowed per call.');

it('rejects an unsupported statement type', function () {
    $this->app->make(DatabaseIntrospectionService::class)->runQuery('testing', 'DROP TABLE widgets');
})->throws(InvalidArgumentException::class);

it('rejects a write query when allow_write_queries is disabled (the default)', function () {
    $this->app->make(DatabaseIntrospectionService::class)
        ->runQuery('testing', "INSERT INTO widgets (name, amount) VALUES ('Gamma', 30)");
})->throws(InvalidArgumentException::class, 'Write queries (INSERT/UPDATE/DELETE) are disabled');

it('allows a write query once allow_write_queries is enabled', function () {
    config(['laravel-mcp-pilot.database.allow_write_queries' => true]);

    $result = $this->app->make(DatabaseIntrospectionService::class)
        ->runQuery('testing', "INSERT INTO widgets (name, amount) VALUES ('Gamma', 30)");

    expect($result['affected_rows'])->toBe(1)
        ->and(DB::connection('testing')->table('widgets')->count())->toBe(3);
});
