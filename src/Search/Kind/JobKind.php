<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use ReflectionClass;

/** Also matches queued Mailables/Notifications, which implement the same ShouldQueue marker. */
class JobKind implements PhpKindDetector
{
    public function supports(ReflectionClass $class): bool
    {
        return is_subclass_of($class->getName(), ShouldQueue::class);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'job';
    }

    public function context(ReflectionClass $class): array
    {
        return [];
    }
}
