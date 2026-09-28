<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Console\Command;
use ReflectionClass;

class CommandKind implements PhpKindDetector
{
    public function supports(ReflectionClass $class): bool
    {
        return is_subclass_of($class->getName(), Command::class);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'command';
    }

    /**
     * Reads the $signature/$name default property value directly, rather than cross-referencing
     * Artisan::all() — that registry is only populated once the console kernel has actually
     * booted commands, which isn't guaranteed when the index is built from an HTTP request.
     */
    public function context(ReflectionClass $class): array
    {
        $defaults = $class->getDefaultProperties();

        return array_filter(['signature' => $defaults['signature'] ?? $defaults['name'] ?? null]);
    }
}
