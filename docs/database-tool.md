# Database Tool

`DatabaseIntrospectionService` reads schema and data from any Laravel-configured connection via `Schema`/`DB` — driver-agnostic since Laravel 11's unified schema introspection (`getTables`/`getColumns`/`getIndexes`/`getForeignKeys`) works the same across mysql/pgsql/sqlite/sqlsrv.

## Basic Usage

```php
use GomdimApps\LaravelMCPPilot\Database\DatabaseIntrospectionService;

$db = app(DatabaseIntrospectionService::class);

$db->listTables('pgsql', 'thing');      // tables whose name contains "thing", with row_count
$db->describeTable('pgsql', 'things');  // columns, indexes, foreign keys (+ spatie_map if applicable)
$db->runQuery('pgsql', 'SELECT * FROM things WHERE amount > 100');
```

## Why no tag-based extension point

Unlike the Search tool's indexers, `listTables()`/`describeTable()`/`runQuery()` are one cohesive capability, not a set of independent contributors — so `DatabaseIntrospectionService` is registered as a plain singleton, without `tag()`, inside `DatabaseServiceProvider`. There's nothing to extend here from a consuming app.

## Config

Published under the `database` key of `config/laravel-mcp-pilot.php`:

| Key | Purpose |
|---|---|
| `max_rows` | Row cap appended as `LIMIT` to an unbounded `SELECT` (default `200`) |
| `allow_write_queries` | Enables `INSERT`/`UPDATE`/`DELETE` via `runQuery()` — **disabled by default** |
| `spatie_permission.enabled` | Force-disables the Spatie integration even when the package is installed |

`runQuery()` always rejects multi-statement payloads and anything that isn't `SELECT`/`WITH`/`INSERT`/`UPDATE`/`DELETE` — no DDL/DCL in any configuration.

## Spatie Permission Integration

If `spatie/laravel-permission` is installed, `describeTable()` automatically adds a `spatie_map` key to the response for `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, and `role_has_permissions` — the roles↔permissions graph resolved via Eloquent relationships instead of hand-joining pivot tables. Without the package installed (or with `spatie_permission.enabled` set to `false`), that key simply doesn't appear.

Role/Permission model classes are resolved through Spatie's own `permission.models.role`/`permission.models.permission` config, so a consuming app's custom models are respected — never hardcoded to Spatie's defaults.

```php
$db->describeTable('pgsql', 'roles');
// [
//     'table' => 'roles',
//     'columns' => [...],
//     'indexes' => [...],
//     'foreign_keys' => [...],
//     'spatie_map' => [
//         'roles' => [['name' => 'editor', 'guard_name' => 'web', 'permissions' => ['edit articles']]],
//         'permissions' => [['name' => 'edit articles', 'guard_name' => 'web', 'roles' => ['editor']]],
//     ],
// ]
```

## MCP Integration Example

```php
class DescribeDatabaseTableTool extends Tool
{
    public function __construct(private readonly DatabaseIntrospectionService $db) {}

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'connection' => ['required', 'string'],
            'table' => ['required', 'string'],
        ]);

        return Response::structured($this->db->describeTable($data['connection'], $data['table']));
    }
}
```

The package doesn't know anything about MCP — it just returns arrays. Any consumer (MCP tool, artisan command, HTTP controller) works the same way.
