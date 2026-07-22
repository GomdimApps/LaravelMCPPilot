<?php

namespace GomdimApps\LaravelMCPPilot\Search\Contracts;

interface PhpSchemaExtractor
{
    public function supports(string $class): bool;

    /**
     * @return array<string, mixed>
     */
    public function extract(string $class, string $source): array;
}
