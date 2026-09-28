<?php

namespace GomdimApps\LaravelMCPPilot\Search;

use Illuminate\Support\ServiceProvider;
use GomdimApps\LaravelMCPPilot\Search\Indexers\CoreFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\FrontendIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\PhpClassIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\RouteIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\SupportFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Persistence\SearchIndexRepository;
use GomdimApps\LaravelMCPPilot\Search\Schema\FormRequestSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\ModelSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\TypeScriptSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\VueComponentSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use GomdimApps\LaravelMCPPilot\Search\Support\Tokenizer;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchIndexRepository::class);

        $this->app->singleton(FileScanner::class, fn () => new FileScanner(config('laravel-mcp-pilot.search.excluded_paths')));

        // Extension point 1: add your own PhpSchemaExtractor/FrontendSchemaExtractor from your
        // app's own ServiceProvider by tagging it into these same container tags — no fork needed.
        collect([FormRequestSchema::class, ModelSchema::class])->each(fn (string $schema) => $this->app->tag($schema, 'laravel-mcp-pilot.php-schemas'));
        collect([VueComponentSchema::class, TypeScriptSchema::class])->each(fn (string $schema) => $this->app->tag($schema, 'laravel-mcp-pilot.frontend-schemas'));

        $this->app->singleton(PhpClassIndexer::class, fn ($app) => new PhpClassIndexer(
            $app->make(FileScanner::class),
            collect($app->tagged('laravel-mcp-pilot.php-schemas')),
            config('laravel-mcp-pilot.search.php'),
        ));

        $this->app->singleton(FrontendIndexer::class, fn ($app) => new FrontendIndexer(
            $app->make(FileScanner::class),
            collect($app->tagged('laravel-mcp-pilot.frontend-schemas')),
            config('laravel-mcp-pilot.search.frontend_root'),
        ));

        $this->app->singleton(RouteIndexer::class, fn () => new RouteIndexer(config('laravel-mcp-pilot.search.controllers_namespace')));

        $this->app->singleton(SupportFileIndexer::class, fn ($app) => new SupportFileIndexer(
            $app->make(FileScanner::class),
            config('laravel-mcp-pilot.search.support_paths'),
            config('laravel-mcp-pilot.search.support_extensions'),
        ));

        $this->app->singleton(CoreFileIndexer::class, fn ($app) => new CoreFileIndexer(
            $app->make(FileScanner::class),
            config('laravel-mcp-pilot.search.core_paths'),
        ));

        // Extension point 2: add your own Indexer (e.g. a Livewire component indexer) by tagging
        // it into 'laravel-mcp-pilot.indexers' from your app's provider — SearchService picks
        // up anything tagged here automatically, in any order.
        collect([
            PhpClassIndexer::class,
            RouteIndexer::class,
            FrontendIndexer::class,
            SupportFileIndexer::class,
            CoreFileIndexer::class,
        ])->each(fn (string $indexer) => $this->app->tag($indexer, 'laravel-mcp-pilot.indexers'));

        $this->app->singleton(SearchService::class, fn ($app) => new SearchService(
            collect($app->tagged('laravel-mcp-pilot.indexers')),
            $app->make(Tokenizer::class),
            $app->make(SearchIndexRepository::class),
        ));
    }
}
