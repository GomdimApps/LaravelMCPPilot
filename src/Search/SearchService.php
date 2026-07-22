<?php

namespace GomdimApps\LaravelMCPPilot\Search;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Support\Tokenizer;

/** Thin orchestrator: collects entries from every registered Indexer, persists, and serves search. */
class SearchService
{
    /** @param Collection<int, Indexer> $indexers */
    public function __construct(
        private readonly Collection $indexers,
        private readonly Tokenizer $tokenizer,
    ) {}

    public function refresh(): array
    {
        $index = tap($this->build(), fn (array $built) => File::put($this->cachePath(), json_encode($built)));

        return ['entries' => count($index['entries']), 'terms' => count($index['terms'])];
    }

    public function search(string $term, ?int $limit = null): array
    {
        $index = $this->loadedIndex();
        $queryTerms = $this->tokenizer->tokenize($term);

        $ids = $queryTerms === [] ? collect() : collect($queryTerms)
            ->map(fn (string $query) => $this->tokenizer->postingsFor($index['terms'], $query))
            ->reduce(fn (?array $carry, array $postings) => $carry === null ? $postings : array_values(array_intersect($carry, $postings)));

        $compactQuery = str_replace(' ', '', Str::lower($term));

        $matches = collect($ids)
            ->map(fn (int $id) => $index['entries'][$id])
            ->sortBy(fn (array $entry) => sprintf(
                '%d-%04d',
                str_contains(Str::lower($entry['symbol'] ?? ''), $compactQuery) ? 0 : 1,
                strlen($entry['symbol'] ?? ''),
            ))
            ->values();

        return [
            'indexed_at' => $index['generated_at'],
            'total_matches' => $matches->count(),
            'results' => $matches->take($limit ?? config('laravel-mcp-pilot.search.max_results'))->all(),
        ];
    }

    public function build(): array
    {
        $entries = $this->indexers->flatMap(fn (Indexer $indexer) => $indexer->entries())->values();

        $terms = [];

        foreach ($entries as $id => $entry) {
            foreach ($this->tokenizer->termsFor($entry) as $term) {
                $terms[$term][] = $id;
            }
        }

        ksort($terms);

        return [
            'generated_at' => now()->toIso8601String(),
            'entries' => $entries->all(),
            'terms' => $terms,
        ];
    }

    private function loadedIndex(): array
    {
        $path = $this->cachePath();

        if (File::exists($path) && is_array($cached = json_decode(File::get($path), true))) {
            return $cached;
        }

        return tap($this->build(), fn (array $index) => File::put($path, json_encode($index)));
    }

    private function cachePath(): string
    {
        return config('laravel-mcp-pilot.search.cache_path');
    }
}
