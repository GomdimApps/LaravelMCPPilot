# LaravelMCPPilot

> Token-lean toolkit for Laravel projects — Lexical Search · Database Introspection

**LaravelMCPPilot** gives AI agents (or an MCP server) two cheap ways to explore a Laravel project: a lexical code index (classes, routes, views, frontend files, with schema extraction) and a driver-agnostic database introspection tool (list tables, describe schema, run guarded queries).

---

## Features

- **Lexical search** — classes, routes, views, and frontend files by term, no AST/embeddings
- **Database introspection** — tables, columns, indexes, and foreign keys on any Laravel-supported driver
- **Pluggable indexers & schema extractors** — extend the Search tool from your own app, no fork needed
- **Safe by default** — write queries (INSERT/UPDATE/DELETE) are disabled until explicitly enabled
- **Optional Spatie Permission integration** — auto-detected, zero hard dependency
- **Fully tested** — Pest + Testbench, Docker matrix, CI

---

## Quick Start

```bash
composer require gomdimapps/laravel-mcp-pilot
php artisan vendor:publish --tag=laravel-mcp-pilot-config
```

```php
use GomdimApps\LaravelMCPPilot\Search\SearchService;

$index = app(SearchService::class);
$index->refresh();
$index->search('store thing');
```

---

## Navigation

- [Installation & Requirements](installation.md)
- [Search Tool](search-tool.md)
- [Database Tool](database-tool.md)
- [Testing](testing.md)
- [Limitations & Roadmap](limitations.md)
