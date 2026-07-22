# Installation & Requirements

## Requirements

| Requirement | Version |
|---|---|
| **PHP** | >= 8.3 |
| **Laravel** | ^12.0 or ^13.0 |
| **spatie/laravel-permission** *(optional)* | ^6.0 — enables Spatie role/permission mapping in the Database tool |

## Install via Composer

```bash
composer require gomdimapps/laravel-mcp-pilot
php artisan vendor:publish --tag=laravel-mcp-pilot-config
```

## Config

Publishing creates `config/laravel-mcp-pilot.php` with two top-level keys, one per tool:

- `search` — see [Search Tool](search-tool.md#config)
- `database` — see [Database Tool](database-tool.md#config)

## Architecture

Each tool ships its own `ServiceProvider` (`Search\SearchServiceProvider`, `Database\DatabaseServiceProvider`), auto-registered by the package's main `LaravelMCPPilotServiceProvider` — there's nothing to register manually beyond the `composer require` above. Adding a third tool later is additive: a new provider class plus one line in the main provider, no existing bindings touched.
