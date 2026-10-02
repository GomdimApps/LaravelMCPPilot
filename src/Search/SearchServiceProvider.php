<?php

namespace GomdimApps\LaravelMCPPilot\Search;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use GomdimApps\LaravelMCPPilot\Search\Indexers\CoreFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\FrontendIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\PhpClassIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\RouteIndexer;
use GomdimApps\LaravelMCPPilot\Search\Indexers\SupportFileIndexer;
use GomdimApps\LaravelMCPPilot\Search\Kind\CommandKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ControllerKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\EventKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\JobKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ListenerKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\MiddlewareKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ModelKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\NativeTypeKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\PolicyKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ProviderKind;
use GomdimApps\LaravelMCPPilot\Search\Kind\ServiceKind;
use GomdimApps\LaravelMCPPilot\Search\Persistence\SearchIndexRepository;
use GomdimApps\LaravelMCPPilot\Search\Schema\BladeViewSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\ConfigSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\FactorySchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\FormRequestSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\MigrationSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\ModelSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\SeederSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\TypeScriptSchema;
use GomdimApps\LaravelMCPPilot\Search\Schema\VueComponentSchema;
use GomdimApps\LaravelMCPPilot\Search\Support\FileScanner;
use GomdimApps\LaravelMCPPilot\Search\Support\PhpKindResolver;
use GomdimApps\LaravelMCPPilot\Search\Support\ReflectionSignature;
use GomdimApps\LaravelMCPPilot\Search\Support\Tokenizer;
use GomdimApps\LaravelMCPPilot\Search\Support\UseImportResolver;

class SearchServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchIndexRepository::class);
        $this->app->singleton(FileScanner::class, fn () => new FileScanner(config('laravel-mcp-pilot.search.excluded_paths')));
        $this->app->singleton(ReflectionSignature::class);
        $this->app->singleton(UseImportResolver::class);

        // Extension point 1: add your own PhpSchemaExtractor/FrontendSchemaExtractor/
        // SupportSchemaExtractor from your app's own ServiceProvider by tagging it into these
        // same container tags — no fork needed.
        collect([FormRequestSchema::class, ModelSchema::class])->each(fn (string $schema) => $this->app->tag($schema, 'laravel-mcp-pilot.php-schemas'));
        collect([VueComponentSchema::class, TypeScriptSchema::class])->each(fn (string $schema) => $this->app->tag($schema, 'laravel-mcp-pilot.frontend-schemas'));
        collect([MigrationSchema::class, BladeViewSchema::class, ConfigSchema::class, SeederSchema::class, FactorySchema::class])->each(fn (string $schema) => $this->app->tag($schema, 'laravel-mcp-pilot.support-schemas'));

        // Extension point 2: add your own PhpKindDetector the same way — order matters, first
        // match wins, so a more specific/native detector should be tagged before a looser one.
        $this->app->singleton(ControllerKind::class, fn () => new ControllerKind(config('laravel-mcp-pilot.search.controllers_namespace')));
        $this->app->singleton(MiddlewareKind::class, fn () => new MiddlewareKind(config('laravel-mcp-pilot.search.middleware_namespace')));
        $this->app->singleton(PolicyKind::class, fn () => new PolicyKind(config('laravel-mcp-pilot.search.policy_namespace')));
        $this->app->singleton(ListenerKind::class, fn () => new ListenerKind(config('laravel-mcp-pilot.search.listener_namespace')));
        $this->app->singleton(EventKind::class, fn () => new EventKind(config('laravel-mcp-pilot.search.event_namespace')));
        $this->app->singleton(ServiceKind::class, fn () => new ServiceKind(config('laravel-mcp-pilot.search.service_namespace')));

        collect([
            NativeTypeKind::class,
            CommandKind::class,
            ControllerKind::class,
            MiddlewareKind::class,
            ProviderKind::class,
            JobKind::class,
            PolicyKind::class,
            ListenerKind::class,
            ModelKind::class,
            EventKind::class,
            ServiceKind::class,
        ])->each(fn (string $kind) => $this->app->tag($kind, 'laravel-mcp-pilot.php-kinds'));

        $this->app->singleton(PhpKindResolver::class, fn ($app) => new PhpKindResolver(
            collect($app->tagged('laravel-mcp-pilot.php-kinds')),
        ));

        $this->app->singleton(PhpClassIndexer::class, fn ($app) => new PhpClassIndexer(
            $app->make(FileScanner::class),
            collect($app->tagged('laravel-mcp-pilot.php-schemas')),
            $app->make(PhpKindResolver::class),
            $app->make(ReflectionSignature::class),
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
            collect($app->tagged('laravel-mcp-pilot.support-schemas')),
        ));

        $this->app->singleton(CoreFileIndexer::class, fn ($app) => new CoreFileIndexer(
            $app->make(FileScanner::class),
            config('laravel-mcp-pilot.search.core_paths'),
        ));

        // Extension point 3: add your own Indexer (e.g. a Livewire component indexer) by tagging
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

    /**
     * Every singleton() bound above, in declaration order. Resolving any one of these is what
     * triggers register() to actually run — classes that are only ever tagged here (the Kind
     * detectors with no config dependency, every *Schema class) aren't listed: they resolve via
     * tagged()->make() from inside register() itself, once it's already running.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            SearchIndexRepository::class,
            FileScanner::class,
            ReflectionSignature::class,
            UseImportResolver::class,
            ControllerKind::class,
            MiddlewareKind::class,
            PolicyKind::class,
            ListenerKind::class,
            EventKind::class,
            ServiceKind::class,
            PhpKindResolver::class,
            PhpClassIndexer::class,
            FrontendIndexer::class,
            RouteIndexer::class,
            SupportFileIndexer::class,
            CoreFileIndexer::class,
            SearchService::class,
        ];
    }
}
