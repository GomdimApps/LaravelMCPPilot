<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use GomdimApps\LaravelMCPPilot\Search\Contracts\FrontendSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use Symfony\Component\Finder\SplFileInfo;

class TypeScriptSchema implements FrontendSchemaExtractor
{
    public function __construct(private readonly AnchorExtractor $anchor) {}

    public function supports(SplFileInfo $file): bool
    {
        return in_array($file->getExtension(), ['ts', 'tsx'], true);
    }

    public function extract(string $source): array
    {
        $fields = collect([
            $this->anchor->balanced($source, 'export\s+interface\s+\w+\s*', '{', '}'),
            $this->anchor->balanced($source, 'export\s+type\s+\w+\s*=\s*', '{', '}'),
        ])->filter()->flatMap(fn (string $body) => $this->anchor->fieldNames($body))->unique()->values()->all();

        return array_filter(['interface_fields' => $fields]);
    }
}
