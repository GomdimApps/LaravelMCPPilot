<?php

use GomdimApps\LaravelMCPPilot\Database\DatabaseIntrospectionService;
use GomdimApps\LaravelMCPPilot\Search\Indexers\CoreFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\FrontendIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\PhpClassIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\RouteIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\SupportFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\SearchService;
use GomdimApps\LaravelMCPPilot\Search\Schema\FormRequestSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\ModelSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\TypeScriptSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\VueComponentSchema;

it('merges the package config under the laravel-mcp-pilot key', function () {
    expect(config('laravel-mcp-pilot.search.max_results'))->toBe(30)
        ->and(config('laravel-mcp-pilot.database.max_rows'))->toBe(200)
        ->and(config('laravel-mcp-pilot.database.allow_write_queries'))->toBeFalse();
});

it('tags all five indexers so the service resolves them automatically', function () {
    $indexers = $this->app->tagged('laravel-mcp-pilot.indexers');

    $classes = collect($indexers)->map(fn ($indexer) => $indexer::class)->all();

    expect($classes)->toEqualCanonicalizing([
        PhpClassIndexer::class,
        RouteIndexer::class,
        FrontendIndexer::class,
        SupportFileIndexer::class,
        CoreFileIndexer::class,
    ]);
});

it('tags both php and frontend schema extractor sets', function () {
    $phpSchemas = collect($this->app->tagged('laravel-mcp-pilot.php-schemas'))->map(fn ($s) => $s::class)->all();
    $frontendSchemas = collect($this->app->tagged('laravel-mcp-pilot.frontend-schemas'))->map(fn ($s) => $s::class)->all();

    expect($phpSchemas)->toEqualCanonicalizing([FormRequestSchema::class, ModelSchema::class])
        ->and($frontendSchemas)->toEqualCanonicalizing([VueComponentSchema::class, TypeScriptSchema::class]);
});

it('resolves the SearchService singleton wired with the tagged indexers', function () {
    $service = $this->app->make(SearchService::class);

    expect($service)->toBeInstanceOf(SearchService::class)
        ->and($this->app->make(SearchService::class))->toBe($service);
});

it('resolves the DatabaseIntrospectionService singleton', function () {
    $service = $this->app->make(DatabaseIntrospectionService::class);

    expect($service)->toBeInstanceOf(DatabaseIntrospectionService::class)
        ->and($this->app->make(DatabaseIntrospectionService::class))->toBe($service);
});
