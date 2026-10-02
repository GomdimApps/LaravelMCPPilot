# Search Tool

`SearchService` builds a lexical (not semantic/vector) index of a Laravel + Vue/TS project **as a graph**: every class, route, view, migration, config file, etc. becomes an entry (a node), and known relationships between them — a route pointing at its controller, a Model's `belongsTo`/`hasMany`, a migration's table, a policy's model, a listener's event — become edges you can traverse with `related()`. A single term lookup also returns the exact location (file, symbol, route) **and**, where applicable, the construct's schema — a FormRequest's validation rules, a Model's `$fillable`/relationships, a Vue component's `props`/`emits`/form fields, a TypeScript `interface`'s fields.

Every PHP class also gets a granular `kind` (`model`, `controller`, `middleware`, `provider`, `job`, `policy`, `listener`, `command`, `event`, `interface`, `trait`, `enum`, `service`, or a plain `class` fallback), detected primarily via native Laravel/PHP mechanisms — `is_subclass_of()` against framework base classes, `ReflectionClass::isInterface()/isTrait()/isEnum()`, `Route::getMiddleware()`, `Gate::policies()`, `Event::getRawListeners()`, `app()->getLoadedProviders()` — falling back to a namespace convention only where no native registry exists (events, services).

No AST, no LLM, no embeddings — just native PHP reflection, the Laravel registries above, and a generic anchor + balanced-bracket text engine (`AnchorExtractor`), which is what lets the same engine work across PHP, Blade, Vue, and TypeScript/TSX source.

## Basic Usage

```php
use GomdimApps\LaravelMCPPilot\Search\SearchService;

$index = app(SearchService::class);

$index->refresh();                              // rebuilds and persists the index (a SQLite .db)
$index->search('store thing');                  // search by term, results ranked by relevance
$index->related('App\\Models\\Thing', 'model');  // one-hop graph lookup: what it points to / what points to it
```

`related()` returns `['entry' => ..., 'outgoing' => [...], 'incoming' => [...]]` — each edge has a `type` (e.g. `belongsTo`, `routes_to`, `creates_table`, `uses_middleware`, `references_table`) and, when the target resolved to an indexed entry, its full `entry`. A target that doesn't resolve (e.g. a Blade `@extends` of a layout that isn't itself indexed) still comes back with its raw `target` string, just without an `entry` — so a dangling reference is visible rather than silently dropped.

## Extending

`SearchServiceProvider` doesn't hardcode the list of indexers inside the orchestrator — it registers each `Indexer`/`PhpSchemaExtractor`/`FrontendSchemaExtractor`/`SupportSchemaExtractor`/`PhpKindDetector` in the container via a tag, the same pattern Nova/Horizon/Telescope use for pluggable collections:

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

> **Note:** `SearchServiceProvider` is a `DeferrableProvider` — its bindings/tags only exist
> once something resolves `SearchService` or another of its bound classes for the first time.
> Reading `app()->tagged('laravel-mcp-pilot.indexers')` directly, without ever resolving
> `SearchService::class` first, will see this package's own five built-in indexers as absent.
> Always go through `app(SearchService::class)` (as shown above) rather than reading the tag
> directly.

`SearchService::build()` never needs to know the new indexer exists — it just iterates `$this->indexers` and calls `entries()`.

## Config

Published under the `search` key of `config/laravel-mcp-pilot.php`:

| Key | Purpose |
|---|---|
| `database_path` | Where the built index's SQLite `.db` file is persisted (`refresh()`/`search()`) |
| `max_results` | Default result cap for `search()` |
| `excluded_paths` | Relative-path prefixes never indexed (`vendor`, `node_modules`, `storage`, ...) |
| `php.root` / `php.namespace` | Scan root and namespace prefix for `PhpClassIndexer` |
| `controllers_namespace` | Namespace prefix trimmed from controller actions in `RouteIndexer`; also a fallback for `ControllerKind` when a controller doesn't extend the base `Controller` |
| `middleware_namespace` / `policy_namespace` / `event_namespace` / `listener_namespace` / `service_namespace` | Namespace-convention fallbacks for kinds with no (or an incomplete) native Laravel registry to check against |
| `frontend_root` | Scan root for `FrontendIndexer` (`.vue`/`.ts`/`.tsx`/`.jsx`) — defaults to the whole project, since frontend code isn't guaranteed to live under `resources/js` |
| `core_paths` | Root-level PHP files indexed non-recursively (`bootstrap/`, `public/`) |
| `support_paths` | Per-kind paths for `SupportFileIndexer` (views, lang, config, migrations, factories, seeders, docs, ...) |
| `support_extensions` | File extensions accepted by `SupportFileIndexer` |

A project with `app/` renamed, or a frontend outside `resources/js`, just adjusts the config — no fork needed.

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
