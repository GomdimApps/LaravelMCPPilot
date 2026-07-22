<?php

namespace GomdimApps\LaravelMCPPilot\Search\Contracts;

use Illuminate\Support\Collection;

interface Indexer
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function entries(): Collection;
}
