<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

class ProviderKind implements PhpKindDetector
{
    public function supports(ReflectionClass $class): bool
    {
        return is_subclass_of($class->getName(), ServiceProvider::class);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'provider';
    }

    /** kind is assigned regardless of registration, so a stray/unwired provider file is still findable. */
    public function context(ReflectionClass $class): array
    {
        return ['registered' => (bool) (app()->getLoadedProviders()[$class->getName()] ?? false)];
    }
}
