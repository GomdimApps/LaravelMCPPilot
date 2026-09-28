<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;
use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use GomdimApps\LaravelMCPPilot\Search\Support\PhpKindResolver;
use GomdimApps\LaravelMCPPilot\Search\Support\ReflectionSignature;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\SplFileInfo;

class PhpClassIndexer implements Indexer
{
    /** @param Collection<int, PhpSchemaExtractor> $schemas */
    public function __construct(
        private readonly FileScanner $scanner,
        private readonly Collection $schemas,
        private readonly PhpKindResolver $kinds,
        private readonly ReflectionSignature $signatures,
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

        return rescue(fn () => $this->buildEntry($class, $file), report: false);
    }

    private function buildEntry(string $class, SplFileInfo $file): array
    {
        $reflection = new ReflectionClass($class);
        $ownMethods = $this->ownPublicMethods($reflection, $file->getPathname());
        $methodSignatures = $ownMethods->map(fn (ReflectionMethod $method) => $this->signatures->forMethod($method))->all();

        $kind = $this->kinds->resolve($reflection);
        $schema = $this->schemaFor($class, $file);
        $relations = [
            ...($schema['relations'] ?? []),
            ...$this->requestRelations($ownMethods),
            ...$this->kindRelations($kind),
        ];
        unset($schema['relations']);

        return array_filter([
            ...$kind,
            'symbol' => $class,
            'file' => $this->scanner->relativePath($file),
            'methods' => array_column($methodSignatures, 'name'),
            'signatures' => $methodSignatures,
            ...$this->signatures->forClass($reflection),
            ...$schema,
            'relations' => $relations,
        ], fn ($value) => $value !== [] && $value !== null);
    }

    /** Turns a kind detector's descriptive context (policy_for, listens_to) into graph edges too. */
    private function kindRelations(array $kind): array
    {
        return [
            ...collect($kind['policy_for'] ?? [])->map(fn (string $target) => ['type' => 'policy_for', 'target' => $target])->all(),
            ...collect($kind['listens_to'] ?? [])->map(fn (string $target) => ['type' => 'listens_to', 'target' => $target])->all(),
        ];
    }

    private function schemaFor(string $class, SplFileInfo $file): array
    {
        $extractor = $this->schemas->first(fn (PhpSchemaExtractor $schema) => $schema->supports($class));

        return $extractor ? $extractor->extract($class, File::get($file->getPathname())) : [];
    }

    /**
     * Any method (not just controller actions) that type-hints a FormRequest subclass gets a
     * `validates_with` edge — real reflection on the param's actual type, not a name heuristic.
     *
     * @param  Collection<int, ReflectionMethod>  $methods
     */
    private function requestRelations(Collection $methods): array
    {
        return collect($this->signatures->paramTypesSubclassing($methods, FormRequest::class))
            ->map(fn (string $target) => ['type' => 'validates_with', 'target' => $target])
            ->all();
    }

    /**
     * Only methods whose body lives in this file — filtering by file (not declaring class)
     * also excludes trait methods, which Reflection reports as declared by the using class.
     *
     * @return Collection<int, ReflectionMethod>
     */
    private function ownPublicMethods(ReflectionClass $class, string $path): Collection
    {
        return collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method) => $method->getFileName() === $path)
            ->values();
    }
}
