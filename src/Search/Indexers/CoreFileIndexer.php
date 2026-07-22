<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use Symfony\Component\Finder\SplFileInfo;

/** Root-level framework entrypoints only — deliberately non-recursive per root, so caches/build output stay out. */
class CoreFileIndexer implements Indexer
{
    /** @param array<int, string> $roots */
    public function __construct(
        private readonly FileScanner $scanner,
        private readonly array $roots,
    ) {}

    public function entries(): Collection
    {
        return collect($this->roots)
            ->flatMap(fn (string $root) => File::files($root))
            ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
            ->map(fn (SplFileInfo $file) => ['kind' => 'core', 'symbol' => $file->getFilenameWithoutExtension(), 'file' => $this->scanner->relativePath($file)])
            ->values();
    }
}
