<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use ReflectionClass;

class ControllerKind implements PhpKindDetector
{
    public function __construct(private readonly string $controllersNamespace) {}

    public function supports(ReflectionClass $class): bool
    {
        return is_subclass_of($class->getName(), Controller::class)
            || Str::startsWith($class->getName(), $this->controllersNamespace);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'controller';
    }

    public function context(ReflectionClass $class): array
    {
        return [];
    }
}
