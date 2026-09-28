<?php

namespace GomdimApps\LaravelMCPPilot\Search;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Persistence\SearchIndexRepository;
use GomdimApps\LaravelMCPPilot\Search\Support\Tokenizer;

/** Thin orchestrator: collects entries from every registered Indexer, persists, and serves search. */
class SearchService
{
    /** @param Collection<int, Indexer> $indexers */
    public function __construct(
        private readonly Collection $indexers,
        private readonly Tokenizer $tokenizer,
        private readonly SearchIndexRepository $repository,
    ) {}

    public function refresh(): array
    {
        $index = $this->build();

        $this->repository->persist($index);

        return ['entries' => count($index['entries']), 'terms' => count($index['terms'])];
    }

    public function search(string $term, ?int $limit = null): array
    {
        if ($this->repository->isEmpty()) {
            $this->refresh();
        }

        $queryTerms = $this->tokenizer->tokenize($term);
        $compactQuery = str_replace(' ', '', Str::lower($term));

        $matches = $this->repository->matchingEntries($queryTerms)
            ->sortBy(fn (array $entry) => sprintf(
                '%d-%04d',
                str_contains(Str::lower($entry['symbol'] ?? ''), $compactQuery) ? 0 : 1,
                strlen($entry['symbol'] ?? ''),
            ))
            ->values();

        return [
            'indexed_at' => $this->repository->generatedAt(),
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
}
