<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use GomdimApps\LaravelMCPPilot\Search\Contracts\FrontendSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use Symfony\Component\Finder\SplFileInfo;

/** Single scan over the frontend root, dispatching each .vue/.ts file to its matching schema extractor. */
class FrontendIndexer implements Indexer
{
    /** @param Collection<int, FrontendSchemaExtractor> $schemas */
    public function __construct(
        private readonly FileScanner $scanner,
        private readonly Collection $schemas,
        private readonly string $root,
    ) {}

    public function entries(): Collection
    {
        return $this->scanner->filesUnder($this->root)
            ->filter(fn (SplFileInfo $file) => in_array($file->getExtension(), ['vue', 'ts', 'tsx', 'jsx'], true))
            ->map(fn (SplFileInfo $file) => $this->entry($file))
            ->values();
    }

    private function entry(SplFileInfo $file): array
    {
        $source = File::get($file->getPathname());
        $extractor = $this->schemas->first(fn (FrontendSchemaExtractor $schema) => $schema->supports($file));

        return array_filter([
            'kind' => $file->getExtension(),
            'symbol' => $file->getFilenameWithoutExtension(),
            'file' => $this->scanner->relativePath($file),
            'exports' => in_array($file->getExtension(), ['ts', 'tsx'], true) ? $this->tsExports($source) : [],
            ...($extractor?->extract($source) ?? []),
        ], fn ($value) => $value !== [] && $value !== null);
    }

    private function tsExports(string $source): array
    {
        preg_match_all('/^export\s+(?:const|function|interface|type|default)\s+([A-Za-z0-9_]+)?/m', $source, $matches);

        return array_values(array_unique(array_filter($matches[1])));
    }
}
