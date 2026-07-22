<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;

class ModelSchema implements PhpSchemaExtractor
{
    public function __construct(private readonly AnchorExtractor $anchor) {}

    public function supports(string $class): bool
    {
        return Str::contains($class, '\\Models\\');
    }

    public function extract(string $class, string $source): array
    {
        $body = $this->anchor->balanced($source, '\$fillable\s*=\s*', '[', ']');

        return array_filter(['fillable' => $body ? $this->anchor->quotedItems($body) : []]);
    }
}
