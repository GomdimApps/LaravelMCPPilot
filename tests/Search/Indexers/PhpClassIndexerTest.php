<?php

use GomdimApps\LaravelMCPPilot\Search\Indexers\PhpClassIndexer;
use GomdimApps\LaravelMCPPilot\Search\Schema\FormRequestSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\ModelSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;

beforeEach(function () {
    $anchor = new AnchorExtractor;

    $this->indexer = new PhpClassIndexer(
        new FileScanner([]),
        collect([new FormRequestSchema($anchor), new ModelSchema($anchor)]),
        config('laravel-mcp-pilot.search.php'),
    );
});

it('derives the FQCN from the file path and reflects own public methods', function () {
    $entries = $this->indexer->entries()->keyBy('symbol');

    $model = $entries['App\\Models\\Thing'];

    expect($model['kind'])->toBe('class')
        ->and($model['methods'])->toContain('getDisplayTitleAttribute')
        ->and($model['fillable'])->toBe(['title', 'email', 'amount', 'user_id']);
});

it('dispatches FormRequest classes to the FormRequestSchema extractor', function () {
    $entries = $this->indexer->entries()->keyBy('symbol');

    $request = $entries['App\\Http\\Requests\\StoreThingRequest'];

    expect($request['authorize'])->toBe("can('create', Thing::class)")
        ->and($request['rules'])->toHaveKey('title');
});

it('skips classes that do not autoload into a class-like symbol', function () {
    $entries = $this->indexer->entries();

    expect($entries->every(fn (array $entry) => class_exists($entry['symbol']) || interface_exists($entry['symbol'])))->toBeTrue();
});
