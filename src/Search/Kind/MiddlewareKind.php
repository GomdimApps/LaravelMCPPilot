<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Native: cross-references Route::getMiddleware()/getMiddlewareGroups() (alias => class map).
 * Gap: neither exposes Laravel's global middleware stack (bootstrap/app.php's ->withMiddleware()
 * or the legacy Kernel::$middleware) — there's no getter for that, so a namespace-convention
 * fallback covers global middleware that isn't also registered under an alias.
 */
class MiddlewareKind implements PhpKindDetector
{
    public function __construct(private readonly string $middlewareNamespace) {}

    public function supports(ReflectionClass $class): bool
    {
        return $this->aliasesFor($class->getName()) !== []
            || Str::contains($class->getName(), $this->middlewareNamespace);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'middleware';
    }

    public function context(ReflectionClass $class): array
    {
        return array_filter(['aliases' => $this->aliasesFor($class->getName())]);
    }

    private function aliasesFor(string $class): array
    {
        $aliases = collect(Route::getMiddleware())
            ->filter(fn (string $target) => $target === $class)
            ->keys();

        $groups = collect(Route::getMiddlewareGroups())
            ->filter(fn (array $members) => collect($members)->contains(fn (string $member) => Str::before($member, ':') === $class))
            ->keys();

        return $aliases->merge($groups)->unique()->values()->all();
    }
}
