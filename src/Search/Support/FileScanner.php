<?php

namespace GomdimApps\LaravelMCPPilot\Search\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class FileScanner
{
    /** @param array<int, string> $excludedPaths */
    public function __construct(private readonly array $excludedPaths) {}

    /**
     * @return Collection<int, SplFileInfo>
     */
    public function filesUnder(string $path): Collection
    {
        return (File::isDirectory($path) ? collect(File::allFiles($path)) : collect())
            ->reject(fn (SplFileInfo $file) => Str::startsWith($this->relativePath($file), array_map(fn (string $dir) => $dir.'/', $this->excludedPaths)));
    }

    public function relativePath(SplFileInfo $file): string
    {
        return Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR);
    }
}
