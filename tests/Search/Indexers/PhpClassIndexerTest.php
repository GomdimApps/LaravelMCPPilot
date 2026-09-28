<?php

use GomdimApps\LaravelMCPPilot\Search\Indexers\PhpClassIndexer;
use GomdimApps\LaravelMCPPilot\Search\Kind\ControllerKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ModelKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\NativeTypeKind;
use GomdimApps\LaravelMCPPilot\Search\Schema\FormRequestSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\ModelSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use GomdimApps\LaravelMCPPilot\Search\Support\PhpKindResolver;
use GomdimApps\LaravelMCPPilot\Search\Support\ReflectionSignature;
use GomdimApps\LaravelMCPPilot\Search\Support\UseImportResolver;

beforeEach(function () {
    $anchor = new AnchorExtractor;

    $this->indexer = new PhpClassIndexer(
        new FileScanner([]),
        collect([new FormRequestSchema($anchor), new ModelSchema($anchor, new UseImportResolver)]),
        new PhpKindResolver(collect([new NativeTypeKind, new ControllerKind('App\\Http\\Controllers\\'), new ModelKind])),
        new ReflectionSignature,
        config('laravel-mcp-pilot.search.php'),
    );
});

it('derives the FQCN from the file path and reflects own public methods', function () {
    $entries = $this->indexer->entries()->keyBy('symbol');

    $model = $entries['App\\Models\\Thing'];

    expect($model['kind'])->toBe('model')
        ->and($model['extends'])->toBe('Illuminate\\Database\\Eloquent\\Model')
        ->and($model['methods'])->toContain('getDisplayTitleAttribute', 'user')
        ->and($model['fillable'])->toBe(['title', 'email', 'amount', 'user_id']);
});

it('dispatches FormRequest classes to the FormRequestSchema extractor', function () {
    $entries = $this->indexer->entries()->keyBy('symbol');

    $request = $entries['App\\Http\\Requests\\StoreThingRequest'];

    expect($request['authorize'])->toBe("can('create', Thing::class)")
        ->and($request['rules'])->toHaveKey('title');
});

it('classifies a controller via the controllers_namespace fallback, since the fixture does not extend the base Controller', function () {
    $entries = $this->indexer->entries()->keyBy('symbol');

    expect($entries['App\\Http\\Controllers\\ThingController']['kind'])->toBe('controller');
});

it('captures native reflection signatures (params/returns) for every method', function () {
    $entries = $this->indexer->entries()->keyBy('symbol');

    $signatures = collect($entries['App\\Models\\Thing']['signatures'])->keyBy('name');

    expect($signatures['getDisplayTitleAttribute']['returns'])->toBe('string')
        ->and($signatures['user']['returns'])->toBe('Illuminate\\Database\\Eloquent\\Relations\\BelongsTo');
});

it('skips classes that do not autoload into a class-like symbol', function () {
    $entries = $this->indexer->entries();

    expect($entries->every(fn (array $entry) => class_exists($entry['symbol']) || interface_exists($entry['symbol']) || trait_exists($entry['symbol']) || enum_exists($entry['symbol'])))->toBeTrue();
});
