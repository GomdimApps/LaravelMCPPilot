<?php

namespace GomdimApps\LaravelMCPPilot\Search\Contracts;

use Symfony\Component\Finder\SplFileInfo;

interface FrontendSchemaExtractor
{
    public function supports(SplFileInfo $file): bool;

    /**
     * @return array<string, mixed>
     */
    public function extract(string $source): array;
}
