<?php

namespace GomdimApps\LaravelMCPPilot\Database;

use GomdimApps\LaravelMCPPilot\Database\Support\KeywordSearch;
use GomdimApps\LaravelMCPPilot\Database\Support\SpatiePermissionIntrospector;
use GomdimApps\LaravelMCPPilot\Database\Support\SqlStatementGuard;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Reads schema/data from any Laravel-configured connection via Schema/DB — driver-agnostic since
 * Laravel 11's unified schema introspection (getTables/getColumns/getIndexes/getForeignKeys) works
 * the same across mysql/pgsql/sqlite/sqlsrv.
 */
class DatabaseIntrospectionService
{
    public function __construct(
        private readonly SqlStatementGuard $guard,
        private readonly KeywordSearch $keywordSearch,
        private readonly SpatiePermissionIntrospector $spatie,
        private readonly bool $allowWriteQueries,
    ) {}

    public function listTables(string $connection, ?string $keyword = null): array
    {
        return collect(Schema::connection($connection)->getTables())
            ->filter(fn (array $table) => ! $keyword || $this->keywordSearch->matches($keyword, $table['name']))
            ->map(fn (array $table) => [
                'name' => $table['name'],
                'row_count' => DB::connection($connection)->table($table['name'])->count(),
                'size_bytes' => $table['size'],
            ])
            ->values()
            ->all();
    }

    public function describeTable(string $connection, string $table): array
    {
        $schema = Schema::connection($connection);

        $description = [
            'table' => $table,
            'columns' => $schema->getColumns($table),
            'indexes' => $schema->getIndexes($table),
            'foreign_keys' => $schema->getForeignKeys($table),
        ];

        if ($spatieMap = $this->spatie->map($connection, $table)) {
            $description['spatie_map'] = $spatieMap;
        }

        return $description;
    }

    public function runQuery(string $connection, string $query): array
    {
        $normalized = trim($query);
        $this->guard->assertSingleStatement($normalized);

        $type = $this->guard->statementType($normalized);
        $db = DB::connection($connection);

        return match (true) {
            $this->guard->isRead($type) => $this->selectRows($db, $normalized),
            $this->guard->isWrite($type) => $this->writeRows($db, $normalized),
            default => throw new InvalidArgumentException("Statement type {$type} is not allowed; only SELECT, WITH, INSERT, UPDATE, and DELETE are supported (no DDL/DCL)."),
        };
    }

    private function selectRows(ConnectionInterface $db, string $query): array
    {
        $rows = $db->select($this->guard->capSelect($query));

        return [
            'rows' => array_map(fn ($row) => (array) $row, $rows),
            'count' => count($rows),
            'truncated' => count($rows) >= $this->guard->maxRows(),
        ];
    }

    private function writeRows(ConnectionInterface $db, string $query): array
    {
        if (! $this->allowWriteQueries) {
            throw new InvalidArgumentException('Write queries (INSERT/UPDATE/DELETE) are disabled; enable them via the laravel-mcp-pilot.database.allow_write_queries config key.');
        }

        return ['affected_rows' => $db->affectingStatement($query)];
    }
}
