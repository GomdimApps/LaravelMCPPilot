# Installation & Requirements

## Requirements

| Requirement | Version |
|---|---|
| **PHP** | >= 8.3 |
| **Laravel** | ^12.0 or ^13.0 |
| **spatie/laravel-permission** *(optional)* | ^6.0 — enables Spatie role/permission mapping in the Database tool |

## Install via Composer

```bash
composer require --dev gomdimapps/laravel-mcp-pilot
php artisan vendor:publish --tag=laravel-mcp-pilot-config
```

## Config

Publishing creates `config/laravel-mcp-pilot.php` with three top-level keys:

- `enabled` — see "Why `--dev`, and the `enabled` flag" below
- `search` — see [Search Tool](search-tool.md#config)
- `database` — see [Database Tool](database-tool.md#config)

## Why `--dev`, and the `enabled` flag

This package is a dev-only tool: it exists so an AI coding agent can talk to your project
over MCP while you're developing, not to run inside a real request. Installing it with
`--dev` means `composer install --no-dev` — what a production deploy should already be
running — skips it entirely: no code from this package even reaches the production
`vendor/` directory. Get this part right first.

For setups where dev/prod `vendor/` isn't cleanly separated (a shared `vendor/`, a reused
Docker image, a `composer install` without `--no-dev` in some environment), set
`laravel-mcp-pilot.enabled` — defaults to `env('LARAVEL_MCP_PILOT_ENABLED', true)` — to
`false` via that env var wherever this package must never run even if it's present. It
defaults to `true` rather than switching on `APP_ENV` automatically, so whether the package
is active never depends on an implicit environment guess — only `--dev` and this flag, both
explicit. If you run `php artisan config:cache`, this flag is baked in at build time like
everything else here, so build/deploy per environment as usual.

Both `Search\SearchServiceProvider` and `Database\DatabaseServiceProvider` are also
`DeferrableProvider`s — even when the package is present and enabled, none of their
bindings/tags are registered until something actually resolves one of the services they
provide (`SearchService`, `DatabaseIntrospectionService`, ...). A normal request that never
touches either tool pays no cost beyond the one-time config merge.

## Architecture

Each tool ships its own `ServiceProvider` (`Search\SearchServiceProvider`, `Database\DatabaseServiceProvider`), auto-registered by the package's main `LaravelMCPPilotServiceProvider` — there's nothing to register manually beyond the `composer require` above. Adding a third tool later is additive: a new provider class plus one line in the main provider, no existing bindings touched.
