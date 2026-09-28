<?php

use App\Http\Controllers\ThingController;
use GomdimApps\LaravelMCPPilot\Search\Persistence\Models\Entry;
use GomdimApps\LaravelMCPPilot\Search\SearchService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function () {
    Route::get('/things', [ThingController::class, 'index'])->name('things.index');
    Route::post('/things', [ThingController::class, 'store'])->name('things.store');

    $this->service = $this->app->make(SearchService::class);
});

it('builds an index with entries from every registered indexer', function () {
    $index = $this->service->build();

    $kinds = collect($index['entries'])->pluck('kind')->unique()->values()->all();

    expect($index)->toHaveKeys(['generated_at', 'entries', 'terms'])
        ->and($kinds)->toContain('class', 'route', 'vue', 'ts', 'view', 'lang', 'migration', 'factory', 'seeder', 'doc', 'core');
});

it('produces a stable set of entries across consecutive builds, aside from the timestamp', function () {
    $first = collect($this->service->build()['entries']);
    $second = collect($this->service->build()['entries']);

    expect($second->all())->toEqual($first->all());
});

it('matches the frozen baseline index captured for the fixture app', function () {
    // Route entries are excluded: Testbench's own skeleton app registers a couple of named
    // storage routes internally, and that set isn't guaranteed stable across Laravel versions —
    // everything else here comes entirely from our own fixture tree and is fully deterministic.
    // 'file' values are normalized to a path relative to tests/Fixtures/, since the fixture tree
    // isn't under Testbench's base_path() and FileScanner therefore reports it as an absolute
    // path (see TestCase) — one whose prefix differs between a local checkout and CI.
    $entries = collect($this->service->build()['entries'])
        ->reject(fn (array $entry) => $entry['kind'] === 'route')
        ->map(function (array $entry) {
            $entry['file'] = Str::after($entry['file'], 'tests/Fixtures/');

            return $entry;
        })
        ->values()
        ->all();

    $expected = json_decode(file_get_contents(__DIR__.'/../Fixtures/expected-index.json'), true);

    expect($entries)->toEqual($expected);
});

it('persists the built index into the search database on refresh', function () {
    $summary = $this->service->refresh();

    expect($summary)->toHaveKeys(['entries', 'terms'])
        ->and(Entry::query()->count())->toBe($summary['entries']);
});

it('searches the cached index by term, ranking symbol matches first', function () {
    $this->service->refresh();

    $results = $this->service->search('thing');

    expect($results['total_matches'])->toBeGreaterThan(0)
        ->and(collect($results['results'])->pluck('symbol'))->toContain('App\\Models\\Thing');
});

it('returns no matches for a term with no postings', function () {
    $this->service->refresh();

    expect($this->service->search('nonexistentzzz')['total_matches'])->toBe(0);
});

it('creates a self-contained .db file with only its own tables, no Laravel migrations bookkeeping', function () {
    $path = sys_get_temp_dir().'/laravel-mcp-pilot-file-test.db';
    @unlink($path);

    config(['laravel-mcp-pilot.search.database_path' => $path]);

    $this->app->make(SearchService::class)->refresh();

    $tables = collect(Schema::connection('laravel-mcp-pilot')->getTables())->pluck('name');

    expect(File::exists($path))->toBeTrue()
        ->and($tables->all())->toEqualCanonicalizing(['search_entries', 'search_terms', 'search_meta'])
        ->and($tables)->not->toContain('migrations');

    @unlink($path);
});
