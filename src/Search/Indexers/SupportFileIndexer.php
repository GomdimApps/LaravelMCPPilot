<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use Symfony\Component\Finder\SplFileInfo;

class SupportFileIndexer implements Indexer
{
    /** @param array<string, string> $paths kind => path */
    public function __construct(
        private readonly FileScanner $scanner,
        private readonly array $paths,
        private readonly array $extensions,
    ) {}

    public function entries(): Collection
    {
        return collect($this->paths)
            ->flatMap(fn (string $path, string $kind) => $this->scanner->filesUnder($path)
                ->filter(fn (SplFileInfo $file) => in_array($file->getExtension(), $this->extensions, true))
                ->map(fn (SplFileInfo $file) => [
                    'kind' => $kind,
                    'symbol' => Str::before($file->getFilename(), '.'),
                    'file' => $this->scanner->relativePath($file),
                ]))
            ->values();
    }
}
