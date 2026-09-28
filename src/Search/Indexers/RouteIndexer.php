<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\Indexer;

class RouteIndexer implements Indexer
{
    public function __construct(private readonly string $controllersNamespace) {}

    public function entries(): Collection
    {
        return collect(RouteFacade::getRoutes())
            ->filter(fn (Route $route) => $route->getName())
            ->map(fn (Route $route) => $this->entry($route))
            ->values();
    }

    private function entry(Route $route): array
    {
        $action = $route->getActionName();
        $controller = $action !== 'Closure' ? Str::before($action, '@') : null;

        $relations = [
            ...($controller ? [['type' => 'routes_to', 'target' => $controller]] : []),
            ...collect($route->middleware())
                ->map(fn (string $middleware) => ['type' => 'uses_middleware', 'target' => Str::before($middleware, ':')])
                ->all(),
        ];

        return array_filter([
            'kind' => 'route',
            'symbol' => $route->getName(),
            'route' => collect($route->methods())->first(fn (string $method) => $method !== 'HEAD').' /'.ltrim($route->uri(), '/'),
            'action' => Str::after($action, $this->controllersNamespace),
            'relations' => $relations,
        ]);
    }
}
