<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use ReflectionClass;

/** No native base class/interface for "service" exists — lowest-confidence, namespace-only bucket, registered last so it never steals a class a native detector already claimed. */
class ServiceKind implements PhpKindDetector
{
    public function __construct(private readonly string $serviceNamespace) {}

    public function supports(ReflectionClass $class): bool
    {
        return str_contains($class->getName(), $this->serviceNamespace);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'service';
    }

    public function context(ReflectionClass $class): array
    {
        return [];
    }
}
