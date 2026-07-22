<?php

namespace GomdimApps\LaravelMCPPilot\Search\Indexers;

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
            ->filter(fn ($route) => $route->getName())
            ->map(fn ($route) => [
                'kind' => 'route',
                'symbol' => $route->getName(),
                'route' => collect($route->methods())->first(fn (string $method) => $method !== 'HEAD').' /'.ltrim($route->uri(), '/'),
                'action' => Str::after($route->getActionName(), $this->controllersNamespace),
            ])
            ->values();
    }
}
