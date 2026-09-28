<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\SupportSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use Symfony\Component\Finder\SplFileInfo;

class MigrationSchema implements SupportSchemaExtractor
{
    public function __construct(private readonly AnchorExtractor $anchor) {}

    public function supports(string $kind): bool
    {
        return $kind === 'migration';
    }

    /** Only the first Schema::create()/table() call — Laravel's own make:migration scaffolding is one table per file. */
    public function extract(SplFileInfo $file, string $source): array
    {
        if (! preg_match('/Schema::(create|table)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*function\s*\(\s*Blueprint\s*\$table\s*\)/s', $source, $head)) {
            return [];
        }

        [, $verb, $table] = $head;

        $afterPattern = 'Schema::'.$verb.'\(\s*[\'"]'.preg_quote($table, '/').'[\'"]\s*,\s*function\s*\(\s*Blueprint\s*\$table\s*\)[^{]*';
        $body = $this->anchor->balanced($source, $afterPattern, '{', '}');

        return array_filter([
            'table' => $table,
            'columns' => $body ? $this->columns($body) : [],
            'aliases' => [$table],
            'relations' => [['type' => 'creates_table', 'target' => $table]],
        ]);
    }

    private function columns(string $body): array
    {
        preg_match_all('/\$table->[^;]+;/', $body, $statements);

        return collect($statements[0])
            ->map(fn (string $statement) => $this->column($statement))
            ->filter()
            ->values()
            ->all();
    }

    private function column(string $statement): ?array
    {
        if (! preg_match('/\$table->([a-zA-Z_]+)\(/', $statement, $type)) {
            return null;
        }

        preg_match('/\$table->[a-zA-Z_]+\(\s*[\'"]([^\'"]+)[\'"]/', $statement, $name);

        return array_filter([
            'name' => $name[1] ?? $type[1],
            'type' => $type[1],
            'foreign' => $this->foreignTable($statement, $name[1] ?? $type[1]),
        ], fn ($value) => $value !== null);
    }

    /** Best-effort: an explicit table wins, else Laravel's own foreignId('x_id') naming convention. */
    private function foreignTable(string $statement, string $columnName): ?string
    {
        return match (true) {
            (bool) preg_match('/->constrained\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $statement, $m) => $m[1],
            (bool) preg_match('/->on\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $statement, $m) => $m[1],
            str_contains($statement, '->constrained(') => Str::plural(Str::beforeLast($columnName, '_id')),
            default => null,
        };
    }
}
