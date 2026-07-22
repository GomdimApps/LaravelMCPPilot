<?php

use GomdimApps\LaravelMCPPilot\Search\Indexers\CoreFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;

it('indexes root-level PHP files under each configured core path, non-recursively', function () {
    $indexer = new CoreFileIndexer(new FileScanner([]), config('laravel-mcp-pilot.search.core_paths'));

    $symbols = $indexer->entries()->pluck('symbol');

    expect($symbols)->toContain('app', 'index')
        ->and($indexer->entries()->first())->toHaveKeys(['kind', 'symbol', 'file'])
        ->and($indexer->entries()->first()['kind'])->toBe('core');
});
