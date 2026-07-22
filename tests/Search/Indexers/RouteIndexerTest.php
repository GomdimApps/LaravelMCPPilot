<?php

use App\Http\Controllers\ThingController;
use GomdimApps\LaravelMCPPilot\Search\Indexers\RouteIndexer;
use Illuminate\Support\Facades\Route;

it('indexes only named routes, with method/uri and a namespace-trimmed controller action', function () {
    Route::get('/things', [ThingController::class, 'index'])->name('things.index');
    Route::post('/things', [ThingController::class, 'store'])->name('things.store');
    Route::get('/unnamed-things', [ThingController::class, 'index']);

    $indexer = new RouteIndexer('App\\Http\\Controllers\\');

    // Testbench's own skeleton app registers a couple of named storage routes of its own —
    // filter down to the ones this test registered so the assertion isn't coupled to that.
    $entries = $indexer->entries()->keyBy('symbol')->filter(fn ($entry, $name) => str_starts_with($name, 'things.'));

    expect($entries)->toHaveCount(2)
        ->and($entries['things.index']['kind'])->toBe('route')
        ->and($entries['things.index']['route'])->toBe('GET /things')
        ->and($entries['things.index']['action'])->toBe('ThingController@index')
        ->and($entries['things.store']['route'])->toBe('POST /things');
});
