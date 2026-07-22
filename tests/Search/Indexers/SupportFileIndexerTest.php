<?php

use GomdimApps\LaravelMCPPilot\Search\Indexers\SupportFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;

it('indexes every configured support path, tagged by its configured kind', function () {
    $indexer = new SupportFileIndexer(
        new FileScanner([]),
        config('laravel-mcp-pilot.search.support_paths'),
        config('laravel-mcp-pilot.search.support_extensions'),
    );

    $kinds = $indexer->entries()->pluck('kind')->unique()->values()->all();

    expect($kinds)->toContain('view', 'lang', 'migration', 'factory', 'seeder', 'doc')
        ->and($indexer->entries()->firstWhere('symbol', 'thing'))->not->toBeNull();
});
