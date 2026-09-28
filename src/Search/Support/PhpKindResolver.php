<?php

namespace GomdimApps\LaravelMCPPilot\Search\Support;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Support\Collection;
use ReflectionClass;

class PhpKindResolver
{
    /** @param Collection<int, PhpKindDetector> $detectors */
    public function __construct(private readonly Collection $detectors) {}

    /** @return array<string, mixed> */
    public function resolve(ReflectionClass $class): array
    {
        $detector = $this->detectors->first(fn (PhpKindDetector $detector) => $detector->supports($class));

        return $detector
            ? ['kind' => $detector->kind($class), ...$detector->context($class)]
            : ['kind' => 'class'];
    }
}
