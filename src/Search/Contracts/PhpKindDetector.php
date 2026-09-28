<?php

namespace GomdimApps\LaravelMCPPilot\Search\Contracts;

use ReflectionClass;

interface PhpKindDetector
{
    public function supports(ReflectionClass $class): bool;

    public function kind(ReflectionClass $class): string;

    /**
     * Extra facts to merge onto the entry (e.g. aliases, registered, policy_for, listens_to).
     *
     * @return array<string, mixed>
     */
    public function context(ReflectionClass $class): array;
}
