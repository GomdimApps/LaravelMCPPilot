<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\UseImportResolver;

class ModelSchema implements PhpSchemaExtractor
{
    private const RELATION_TYPES = [
        'belongsTo', 'hasMany', 'hasOne', 'belongsToMany',
        'hasManyThrough', 'hasOneThrough',
        'morphTo', 'morphMany', 'morphOne', 'morphToMany', 'morphedByMany',
    ];

    public function __construct(
        private readonly AnchorExtractor $anchor,
        private readonly UseImportResolver $imports,
    ) {}

    public function supports(string $class): bool
    {
        return is_subclass_of($class, Model::class);
    }

    public function extract(string $class, string $source): array
    {
        $fillable = $this->anchor->balanced($source, '\$fillable\s*=\s*', '[', ']');
        $relationships = $this->relationships($class, $source);
        $table = $this->table($class, $source);

        $relations = [
            ...collect($relationships)->map(fn (array $relationship) => ['type' => $relationship['type'], 'target' => $relationship['target']])->all(),
            ['type' => 'references_table', 'target' => $table],
        ];

        return array_filter([
            'fillable' => $fillable ? $this->anchor->quotedItems($fillable) : [],
            'relationships' => $relationships,
            'relations' => $relations,
        ]);
    }

    /** Laravel's own default table-name convention (Str::snake(Str::plural(class name))), unless $table is set explicitly. */
    private function table(string $class, string $source): string
    {
        if (preg_match('/\$table\s*=\s*[\'"]([^\'"]+)[\'"]/', $source, $matches)) {
            return $matches[1];
        }

        return Str::snake(Str::plural(class_basename($class)));
    }

    /**
     * Anchored per discovered method name (not per relation type), since a model can have two
     * belongsTo methods and AnchorExtractor::balanced() only ever captures the first match.
     */
    private function relationships(string $class, string $source): array
    {
        $types = implode('|', self::RELATION_TYPES);

        if (! preg_match_all('/function\s+(\w+)\s*\([^)]*\)[^{]*\{[^}]*?return\s+\$this->('.$types.')\s*\(/s', $source, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return collect($matches)
            ->map(function (array $match) use ($class, $source) {
                [, $method, $type] = $match;

                $body = $this->anchor->balanced(
                    $source,
                    'function\s+'.preg_quote($method, '/').'\s*\([^)]*\)[^{]*\{.*?return\s+\$this->'.preg_quote($type, '/').'\s*',
                    '(',
                    ')',
                );

                $target = $body ? $this->resolveTarget($this->anchor->topLevelSegments($body)[0] ?? '', $class, $source) : null;

                return $target ? ['type' => $type, 'method' => $method, 'target' => $target] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveTarget(string $expression, string $modelClass, string $source): ?string
    {
        $target = trim($expression);
        $target = Str::before($target, '::class');
        $target = trim($target, " \t\n\r\0\x0B'\"");
        $target = ltrim($target, '\\');

        if ($target === '') {
            return null;
        }

        if (str_contains($target, '\\')) {
            return $target;
        }

        $resolved = $this->imports->resolve($target, $source);

        return $resolved !== $target ? $resolved : Str::beforeLast($modelClass, '\\').'\\'.$target;
    }
}
