# Search Tool

`SearchService` builds a lexical (not semantic/vector) index of a Laravel + Vue/TS project. A single term lookup returns the exact location (file, symbol, route) **and**, where applicable, the construct's schema — a FormRequest's validation rules, a Model's `$fillable`, a Vue component's `props`/`emits`/form fields, a TypeScript `interface`'s fields.

No AST, no LLM, no embeddings — just native PHP reflection and a generic anchor + balanced-bracket text engine (`AnchorExtractor`), which is what lets the same engine work across PHP, Vue, and TypeScript source.

## Basic Usage

```php
use GomdimApps\LaravelMCPPilot\Search\SearchService;

$index = app(SearchService::class);

$index->refresh();               // rebuilds and persists the index to cache
$index->search('store thing');   // search by term, results ranked by relevance
```

## Extending

`SearchServiceProvider` doesn't hardcode the list of indexers inside the orchestrator — it registers each `Indexer`/`PhpSchemaExtractor`/`FrontendSchemaExtractor` in the container via a tag, the same pattern Nova/Horizon/Telescope use for pluggable collections:

```php
$this->app->tag([FormRequestSchema::class, ModelSchema::class], 'laravel-mcp-pilot.php-schemas');
// ...
$this->app->singleton(SearchService::class, fn ($app) => new SearchService(
    collect($app->tagged('laravel-mcp-pilot.indexers')),
    $app->make(Tokenizer::class),
));
```

A consuming app adds its own indexer without touching the package — just implement `Indexer` (or one of the two `SchemaExtractor` contracts) and tag it from your own provider:

```php
// In your app's AppServiceProvider, for example:
$this->app->tag(LivewireComponentIndexer::class, 'laravel-mcp-pilot.indexers');
```

`SearchService::build()` never needs to know the new indexer exists — it just iterates `$this->indexers` and calls `entries()`.

## Config

Published under the `search` key of `config/laravel-mcp-pilot.php`:

| Key | Purpose |
|---|---|
| `cache_path` | Where the built index is persisted (`refresh()`/`loadedIndex()`) |
| `max_results` | Default result cap for `search()` |
| `excluded_paths` | Relative-path prefixes never indexed (`vendor`, `node_modules`, `storage`, ...) |
| `php.root` / `php.namespace` | Scan root and namespace prefix for `PhpClassIndexer` |
| `controllers_namespace` | Namespace prefix trimmed from controller actions in `RouteIndexer` |
| `frontend_root` | Scan root for `FrontendIndexer` (`.vue` + `.ts`) |
| `core_paths` | Root-level PHP files indexed non-recursively (`bootstrap/`, `public/`) |
| `support_paths` | Per-kind paths for `SupportFileIndexer` (views, lang, migrations, factories, seeders, docs, ...) |
| `support_extensions` | File extensions accepted by `SupportFileIndexer` |

A project with `app/` renamed, or a frontend in `frontend/` instead of `resources/js`, just adjusts the config — no fork needed.

## MCP Integration Example

```php
class SearchProjectTool extends Tool
{
    public function __construct(private readonly SearchService $index) {}

    public function handle(Request $request): Response
    {
        $term = $request->validate(['term' => ['required', 'string']])['term'];

        return Response::structured($this->index->search($term));
    }
}
```

The package doesn't know anything about MCP — it just returns arrays. Any consumer (MCP tool, artisan command, HTTP controller) works the same way.
