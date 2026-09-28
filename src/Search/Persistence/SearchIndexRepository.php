<?php

namespace GomdimApps\LaravelMCPPilot\Search\Persistence;

use GomdimApps\LaravelMCPPilot\Search\Persistence\Models\Entry;
use GomdimApps\LaravelMCPPilot\Search\Persistence\Models\SearchMeta;
use GomdimApps\LaravelMCPPilot\Search\Persistence\Models\Term;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * The only class that talks to the search index's SQLite database. The schema is created directly
 * (Schema::create, guarded by a hasTable check) instead of through Laravel's migration system, so
 * the .db file stays self-contained — no migrations table, nothing for the host app's own
 * `artisan migrate` to track.
 */
class SearchIndexRepository
{
    private bool $ready = false;

    public function persist(array $index): void
    {
        $this->ensureSchema();

        DB::connection(SearchConnection::NAME)->transaction(function () use ($index) {
            Term::query()->delete();
            Entry::query()->delete(); // terms first: they hold a foreign key onto entries

            collect($index['entries'])->values()
                ->map(fn (array $entry, int $id) => [
                    'id' => $id,
                    'kind' => $entry['kind'] ?? null,
                    'symbol' => $entry['symbol'] ?? null,
                    'file' => $entry['file'] ?? null,
                    'payload' => json_encode($entry),
                ])
                ->chunk(500)
                ->each(fn (Collection $chunk) => Entry::insert($chunk->all()));

            collect($index['terms'])
                ->flatMap(fn (array $ids, string $term) => collect($ids)->map(fn (int $id) => [
                    'term' => $term,
                    'entry_id' => $id,
                ]))
                ->chunk(1000)
                ->each(fn (Collection $chunk) => Term::insert($chunk->all()));

            SearchMeta::query()->updateOrCreate(['id' => 1], ['generated_at' => $index['generated_at']]);
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    public function matchingEntries(array $queryTerms): Collection
    {
        $this->ensureSchema();

        if ($queryTerms === [] || ($ids = $this->intersectEntryIds($queryTerms)) === []) {
            return collect();
        }

        return Entry::query()->whereIn('id', $ids)->get()->map(fn (Entry $entry) => $entry->payload);
    }

    public function generatedAt(): ?string
    {
        $this->ensureSchema();

        return SearchMeta::query()->find(1)?->generated_at;
    }

    public function isEmpty(): bool
    {
        $this->ensureSchema();

        return Entry::query()->doesntExist();
    }

    /**
     * One prefix range-scan per query token, intersected in SQL so SQLite does the set
     * intersection using the search_terms index instead of pulling full posting lists into PHP.
     */
    private function intersectEntryIds(array $queryTerms): array
    {
        $subquery = 'select entry_id from search_terms where term >= ? and term < ?';
        $sql = collect($queryTerms)->map(fn () => $subquery)->implode(' intersect ');
        $bindings = collect($queryTerms)->flatMap(fn (string $term) => [$term, $term.chr(255)]);

        return collect(DB::connection(SearchConnection::NAME)->select($sql, $bindings->all()))
            ->pluck('entry_id')
            ->all();
    }

    /**
     * Configures the dedicated connection and creates the schema on first use. Reading the
     * database path here rather than in the service provider guarantees the host app's own config
     * overrides have already applied; the $ready flag keeps that work to once per instance.
     */
    private function ensureSchema(): void
    {
        if ($this->ready) {
            return;
        }

        $path = config('laravel-mcp-pilot.search.database_path');

        config(["database.connections.".SearchConnection::NAME => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        if ($path !== ':memory:' && ! file_exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            touch($path);
        }

        if (! Schema::connection(SearchConnection::NAME)->hasTable('search_entries')) {
            $this->createTables();
        }

        $this->ready = true;
    }

    private function createTables(): void
    {
        Schema::connection(SearchConnection::NAME)->create('search_entries', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('kind')->index();
            $table->string('symbol')->nullable();
            $table->string('file')->nullable();
            $table->json('payload');
        });

        Schema::connection(SearchConnection::NAME)->create('search_terms', function (Blueprint $table) {
            $table->string('term');
            $table->unsignedInteger('entry_id');
            $table->primary(['term', 'entry_id']);
            $table->foreign('entry_id')->references('id')->on('search_entries')->cascadeOnDelete();
        });

        Schema::connection(SearchConnection::NAME)->create('search_meta', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary(); // single row, id = 1
            $table->string('generated_at');
        });

        DB::connection(SearchConnection::NAME)->statement('PRAGMA journal_mode=WAL');
    }
}
