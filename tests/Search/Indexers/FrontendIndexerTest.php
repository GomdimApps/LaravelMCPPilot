<?php

use GomdimApps\LaravelMCPPilot\Search\Indexers\FrontendIndexer;
use GomdimApps\LaravelMCPPilot\Search\Schema\TypeScriptSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\VueComponentSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;

beforeEach(function () {
    $anchor = new AnchorExtractor;

    $this->indexer = new FrontendIndexer(
        new FileScanner([]),
        collect([new VueComponentSchema($anchor), new TypeScriptSchema($anchor)]),
        config('laravel-mcp-pilot.search.frontend_root'),
    );
});

it('dispatches each frontend file to its matching schema extractor', function () {
    $entries = $this->indexer->entries()->keyBy('symbol');

    expect($entries->has('ThingForm'))->toBeTrue()
        ->and($entries->has('thing'))->toBeTrue();

    expect($entries['ThingForm']['kind'])->toBe('vue')
        ->and($entries['ThingForm']['props'])->toBe(['title', 'amount']);

    expect($entries['thing']['kind'])->toBe('ts')
        ->and($entries['thing']['interface_fields'])->toBe(['id', 'title', 'amount']);
});

it('collects top-level TS exports separately from interface fields', function () {
    $entry = $this->indexer->entries()->firstWhere('symbol', 'thing');

    expect($entry['exports'])->toContain('Thing', 'ThingStatus');
});

it('extracts interface fields from a .tsx file the same as .ts', function () {
    $entry = $this->indexer->entries()->firstWhere('symbol', 'Extra');

    expect($entry['kind'])->toBe('tsx')
        ->and($entry['interface_fields'])->toBe(['label']);
});

it('scans the whole given root, not just a resources/js-style subtree', function () {
    $anchor = new AnchorExtractor;
    $indexer = new FrontendIndexer(
        new FileScanner([]),
        collect([new VueComponentSchema($anchor), new TypeScriptSchema($anchor)]),
        __DIR__.'/../../Fixtures',
    );

    $entries = $indexer->entries()->keyBy('symbol');

    expect($entries->has('ThingForm'))->toBeTrue() // under resources/js
        ->and($entries->has('Extra'))->toBeTrue(); // under a sibling frontend/ dir
});
