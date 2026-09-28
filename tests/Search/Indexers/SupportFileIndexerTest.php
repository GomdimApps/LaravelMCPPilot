<?php

use GomdimApps\LaravelMCPPilot\Search\Indexers\SupportFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Schema\BladeViewSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\ConfigSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\FactorySchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\MigrationSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\SeederSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use GomdimApps\LaravelMCPPilot\Search\Support\UseImportResolver;

beforeEach(function () {
    $this->indexer = new SupportFileIndexer(
        new FileScanner([]),
        config('laravel-mcp-pilot.search.support_paths'),
        config('laravel-mcp-pilot.search.support_extensions'),
        collect([
            new MigrationSchema(new AnchorExtractor),
            new BladeViewSchema,
            new ConfigSchema,
            new SeederSchema(new UseImportResolver),
            new FactorySchema(new UseImportResolver),
        ]),
    );
});

it('indexes every configured support path, tagged by its configured kind', function () {
    $entries = $this->indexer->entries();

    $kinds = $entries->pluck('kind')->unique()->values()->all();

    expect($kinds)->toContain('view', 'lang', 'config', 'migration', 'factory', 'seeder', 'doc')
        ->and($entries->firstWhere('symbol', 'thing'))->not->toBeNull();
});

it('extracts the table, columns, and foreign key hint from a migration', function () {
    $migration = $this->indexer->entries()->firstWhere('kind', 'migration');

    $columns = collect($migration['columns'])->keyBy('name');

    expect($migration['table'])->toBe('things')
        ->and($migration['aliases'])->toBe(['things'])
        ->and($columns['user_id']['foreign'])->toBe('users');
});

it('extracts extends/includes/components/sections from a blade view', function () {
    $view = $this->indexer->entries()->firstWhere('kind', 'view');

    expect($view['extends'])->toBe('layouts.app')
        ->and($view['includes'])->toBe(['things.partials.row'])
        ->and($view['components'])->toBe(['components.alert'])
        ->and($view['sections'])->toBe(['title']);
});

it('flattens a config file into a dot-notation key list', function () {
    $config = $this->indexer->entries()->firstWhere('kind', 'config');

    expect($config['keys'])->toContain('default_status', 'notifications.enabled', 'notifications.channels.0');
});

it('extracts the models a seeder references', function () {
    $seeder = $this->indexer->entries()->firstWhere('kind', 'seeder');

    expect($seeder['models'])->toBe(['App\\Models\\Thing']);
});

it('extracts which model a factory belongs to', function () {
    $factory = $this->indexer->entries()->firstWhere('kind', 'factory');

    expect($factory['model'])->toBe('App\\Models\\Thing');
});
