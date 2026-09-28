<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use ReflectionClass;

/** No native, enumerable "all declared events" registry exists (unlike listeners) — namespace convention is mandatory here, not just a fallback. */
class EventKind implements PhpKindDetector
{
    public function __construct(private readonly string $eventNamespace) {}

    public function supports(ReflectionClass $class): bool
    {
        return str_contains($class->getName(), $this->eventNamespace);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'event';
    }

    public function context(ReflectionClass $class): array
    {
        return [];
    }
}
