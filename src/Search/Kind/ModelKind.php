<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

class ModelKind implements PhpKindDetector
{
    public function supports(ReflectionClass $class): bool
    {
        return is_subclass_of($class->getName(), Model::class);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'model';
    }

    public function context(ReflectionClass $class): array
    {
        return [];
    }
}
