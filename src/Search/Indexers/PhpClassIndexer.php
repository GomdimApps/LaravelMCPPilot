<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\SplFileInfo;

class PhpClassIndexer implements Indexer
{
    /** @param Collection<int, PhpSchemaExtractor> $schemas */
    public function __construct(
        private readonly FileScanner $scanner,
        private readonly Collection $schemas,
        private readonly array $php,
    ) {}

    public function entries(): Collection
    {
        return $this->scanner->filesUnder($this->php['root'])
            ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
            ->map(fn (SplFileInfo $file) => $this->classEntry($file))
            ->filter()
            ->values();
    }

    /**
     * Derives the PSR-4 FQCN from the file path and introspects it with native Reflection;
     * files that don't autoload into a class-like symbol (helpers, broken classes) are skipped.
     */
    private function classEntry(SplFileInfo $file): ?array
    {
        $class = Str::of($file->getRelativePathname())->beforeLast('.php')->replace('/', '\\')->prepend($this->php['namespace'].'\\')->toString();

        return rescue(fn () => array_filter([
            'kind' => 'class',
            'symbol' => $class,
            'file' => $this->scanner->relativePath($file),
            'methods' => $this->ownPublicMethods($class, $file->getPathname()),
            ...$this->schemaFor($class, $file),
        ], fn ($value) => $value !== [] && $value !== null), report: false);
    }

    private function schemaFor(string $class, SplFileInfo $file): array
    {
        $extractor = $this->schemas->first(fn (PhpSchemaExtractor $schema) => $schema->supports($class));

        return $extractor ? $extractor->extract($class, File::get($file->getPathname())) : [];
    }

    /**
     * Only methods whose body lives in this file — filtering by file (not declaring class)
     * also excludes trait methods, which Reflection reports as declared by the using class.
     */
    private function ownPublicMethods(string $class, string $path): array
    {
        return collect((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC))->filter(fn (ReflectionMethod $method) => $method->getFileName() === $path)->map(fn (ReflectionMethod $method) => $method->getName())->values()->all();
    }
}
