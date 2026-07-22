# Testing

The suite is built on [Pest](https://pestphp.com/) + [Orchestra Testbench](https://github.com/orchestral/testbench), following the same conventions as [GomdimApps/Tools](https://github.com/GomdimApps/Tools):

- Pure classes (`AnchorExtractor`, `Tokenizer`, the `*Schema` extractors, `SqlStatementGuard`, `KeywordSearch`) are tested in isolation, no container involved.
- The `Indexer`s, both orchestrators, and all three providers (`LaravelMCPPilotServiceProvider` plus the per-tool `SearchServiceProvider`/`DatabaseServiceProvider` it registers) are tested against a fixture app: `tests/Fixtures/` for the Search tool, an in-memory sqlite connection for the Database tool.
- `SearchServiceTest` freezes an expected index (`tests/Fixtures/expected-index.json`) and diffs against it on every run — the "diff against baseline" strategy used throughout this index's original development, now automated.
- The Spatie Permission integration is exercised end-to-end in `DatabaseIntrospectionServiceSpatieTest`. `spatie/laravel-permission` is a dev dependency only, never a runtime one.

## Run Locally

```bash
composer install
vendor/bin/pest
```

## Docker (PHP version matrix)

```bash
make test-8.3   # or test-8.4 / test-8.5
make test-all   # runs all three
```

## CI

`.github/workflows/tests.yml` runs the same suite across a PHP 8.3/8.4/8.5 × Laravel 12/13 matrix.
