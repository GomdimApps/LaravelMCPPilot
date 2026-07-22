<?php

namespace GomdimApps\LaravelMCPPilot\Search\Support;

use Illuminate\Support\Str;

/** Pure inverted-index tokenizer — no project paths, no I/O, safe to reuse standalone. */
class Tokenizer
{
    public function termsFor(array $entry): array
    {
        return $this->tokenize(collect($entry)->flatten()->filter(fn ($value) => is_string($value))->implode(' '));
    }

    /**
     * Splits camelCase/snake_case/kebab-case/path segments into lowercase singular terms,
     * so build and query normalize identically (tickets ≈ ticket, IndexTicket ⇒ index + ticket).
     */
    public function tokenize(string $text): array
    {
        $spaced = preg_replace(['/([a-z0-9])([A-Z])/', '/([A-Z]+)([A-Z][a-z])/'], '$1 $2', $text);

        preg_match_all('/[A-Za-z0-9]+/', $spaced, $matches);

        return collect($matches[0])->map(fn (string $word) => Str::singular(Str::lower($word)))->filter(fn (string $word) => strlen($word) >= 2)->unique()->values()->all();
    }

    public function postingsFor(array $terms, string $query): array
    {
        return collect($terms)->filter(fn (array $ids, string $term) => str_starts_with($term, $query))->flatten()->unique()->values()->all();
    }
}
