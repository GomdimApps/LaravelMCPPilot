<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use ReflectionClass;

/** Interfaces/traits/enums, via plain PHP reflection — no framework needed, must run before every other detector. */
class NativeTypeKind implements PhpKindDetector
{
    public function supports(ReflectionClass $class): bool
    {
        return $class->isInterface() || $class->isTrait() || $class->isEnum();
    }

    public function kind(ReflectionClass $class): string
    {
        return match (true) {
            $class->isInterface() => 'interface',
            $class->isTrait() => 'trait',
            default => 'enum',
        };
    }

    public function context(ReflectionClass $class): array
    {
        return [];
    }
}
