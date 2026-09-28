<?php

namespace GomdimApps\LaravelMCPPilot\Search\Contracts;

use Illuminate\Support\Collection;

interface Indexer
{
    /**
     * Entries have no fixed schema beyond a `kind` discriminator, but two keys are reserved by
     * convention so `SearchService::build()` can turn the index into a graph:
     *
     *   'relations' => [['type' => 'uses', 'target' => 'App\\Models\\Thing'], ...]
     *   'aliases'   => ['things']
     *
     * `target`/`aliases` are symbol strings (FQCN, route name, table name, middleware alias, ...),
     * resolved against every other entry's `symbol`/`aliases` once all indexers have run — an
     * indexer never knows another indexer's output, so it can only ever declare what it points
     * at, not resolve the pointer itself. `aliases` lets an entry be a relation target under an
     * identifier other than its own `symbol` (e.g. a migration's table name). `type` is a free
     * string; suggested vocabulary: uses, routes_to, uses_middleware, belongsTo/hasMany/...,
     * extends_view, includes_view, creates_table, seeds, validates_with, policy_for, listens_to.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function entries(): Collection;
}
