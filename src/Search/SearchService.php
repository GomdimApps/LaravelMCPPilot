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

    /** One-hop graph lookup: what a symbol points to, and what points to it. */
    public function related(string $symbol, ?string $kind = null): array
    {
        if ($this->repository->isEmpty()) {
            $this->refresh();
        }

        return $this->repository->relationsForSymbol($symbol, $kind);
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
            'relations' => $this->resolveRelations($entries),
        ];
    }

    /**
     * Resolves each entry's declared `relations` (see Indexer contract) against every other
     * entry's `symbol`/`aliases` — same one-pass-over-the-flattened-collection trick `terms`
     * already uses, so no indexer needs to know about another indexer's output. A target matching
     * more than one identifier is left unresolved rather than guessed; a target matching none is
     * still recorded (to stays null) so a consumer can see "references X, not indexed" instead of
     * the edge silently disappearing.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return list<array{from: int, to: ?int, to_symbol: string, type: string}>
     */
    private function resolveRelations(Collection $entries): array
    {
        $identifiers = [];

        foreach ($entries as $id => $entry) {
            foreach (array_filter([$entry['symbol'] ?? null, ...($entry['aliases'] ?? [])]) as $identifier) {
                $identifiers[$identifier][] = $id;
            }
        }

        $relations = [];

        foreach ($entries as $id => $entry) {
            foreach ($entry['relations'] ?? [] as $relation) {
                if (empty($relation['type']) || empty($relation['target'])) {
                    continue;
                }

                $matches = $identifiers[$relation['target']] ?? [];

                $relations[] = [
                    'from' => $id,
                    'to' => count($matches) === 1 ? $matches[0] : null,
                    'to_symbol' => $relation['target'],
                    'type' => $relation['type'],
                ];
            }
        }

        return $relations;
    }
}
