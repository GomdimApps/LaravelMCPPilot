<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Contracts\SupportSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use Symfony\Component\Finder\SplFileInfo;

class SupportFileIndexer implements Indexer
{
    /**
     * @param  array<string, string>  $paths kind => path
     * @param  Collection<int, SupportSchemaExtractor>  $extractors
     */
    public function __construct(
        private readonly FileScanner $scanner,
        private readonly array $paths,
        private readonly array $extensions,
        private readonly Collection $extractors,
    ) {}

    public function entries(): Collection
    {
        return collect($this->paths)
            ->flatMap(fn (string $path, string $kind) => $this->scanner->filesUnder($path)
                ->filter(fn (SplFileInfo $file) => in_array($file->getExtension(), $this->extensions, true))
                ->map(fn (SplFileInfo $file) => $this->entry($kind, $file)))
            ->values();
    }

    private function entry(string $kind, SplFileInfo $file): array
    {
        $extractor = $this->extractors->first(fn (SupportSchemaExtractor $extractor) => $extractor->supports($kind));

        return array_filter([
            'kind' => $kind,
            'symbol' => Str::before($file->getFilename(), '.'),
            'file' => $this->scanner->relativePath($file),
            ...($extractor ? $extractor->extract($file, File::get($file->getPathname())) : []),
        ], fn ($value) => $value !== [] && $value !== null);
    }
}
