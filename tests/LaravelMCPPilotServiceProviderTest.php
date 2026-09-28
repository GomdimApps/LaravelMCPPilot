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

it('deep-merges nested search config, so a host that published this file before a newer key existed still gets its default', function () {
    // Regression for a real crash: a host app's already-published config/laravel-mcp-pilot.php
    // predates search.middleware_namespace. Laravel's own mergeConfigFrom() only merges
    // top-level keys ('search', 'database'), so the host's stale, now-incomplete 'search' array
    // would win wholesale and middleware_namespace would resolve to null — a TypeError once
    // passed into MiddlewareKind's non-nullable string constructor param. array_replace_recursive
    // (what LaravelMCPPilotServiceProvider::mergeConfig() actually uses) must recurse instead.
    $packageDefaults = ['search' => ['max_results' => 30, 'middleware_namespace' => '\\Http\\Middleware\\']];
    $staleHostConfig = ['search' => ['max_results' => 99]];

    $merged = array_replace_recursive($packageDefaults, $staleHostConfig);

    expect($merged['search']['max_results'])->toBe(99)
        ->and($merged['search']['middleware_namespace'])->toBe('\\Http\\Middleware\\');
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
