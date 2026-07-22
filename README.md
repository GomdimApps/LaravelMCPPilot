# LaravelMCPPilot

> Token-lean toolkit for Laravel projects — Lexical Search · Database Introspection

**LaravelMCPPilot** gives AI agents (or an MCP server) two cheap ways to explore a Laravel project: a lexical code index (classes, routes, views, frontend files, with schema extraction) and a driver-agnostic database introspection tool (list tables, describe schema, run guarded queries).

[![tests](https://github.com/GomdimApps/LaravelMCPilot/actions/workflows/tests.yml/badge.svg)](https://github.com/GomdimApps/LaravelMCPilot/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## Quick Start

```bash
composer require gomdimapps/laravel-mcp-pilot
php artisan vendor:publish --tag=laravel-mcp-pilot-config
```

### Search

```php
use GomdimApps\LaravelMCPPilot\Search\SearchService;

$index = app(SearchService::class);
$index->refresh();
$index->search('store thing');
```

### Database

```php
use GomdimApps\LaravelMCPPilot\Database\DatabaseIntrospectionService;

$db = app(DatabaseIntrospectionService::class);
$db->listTables('pgsql', 'thing');
$db->describeTable('pgsql', 'things');
```

## Requirements

| Requirement | Version |
|---|---|
| **PHP** | >= 8.3 |
| **Laravel** | ^12.0 or ^13.0 |
| **spatie/laravel-permission** *(optional)* | ^6.0 — enables role/permission mapping in the Database tool |

## Features

- 🔍 **Lexical search** — classes, routes, views, and frontend files by term, no AST/embeddings
- 🗄️ **Database introspection** — tables, columns, indexes, and foreign keys on any Laravel-supported driver
- 🧩 **Pluggable indexers & schema extractors** — extend the Search tool from your own app, no fork needed
- 🔐 **Safe by default** — write queries (INSERT/UPDATE/DELETE) are disabled until explicitly enabled
- 🎭 **Optional Spatie Permission integration** — auto-detected, zero hard dependency
- 🧪 **Fully tested** — Pest + Testbench, Docker matrix, CI

## Documentation

Full documentation is available at [**GomdimApps.github.io/LaravelMCPilot**](https://GomdimApps.github.io/LaravelMCPilot)

Key sections:
- [Installation & Requirements](docs/installation.md)
- [Search Tool](docs/search-tool.md)
- [Database Tool](docs/database-tool.md)
- [Testing](docs/testing.md)
- [Limitations & Roadmap](docs/limitations.md)

## Docker Testing

```bash
make test-8.3   # or test-8.4 / test-8.5
make test-all   # runs all three
```

## License

MIT.
