<?php

namespace GomdimApps\LaravelMCPPilot\Search\Contracts;

use Symfony\Component\Finder\SplFileInfo;

interface SupportSchemaExtractor
{
    /** Dispatch is by the configured `support_paths` kind, not the file extension — view/config/migration/seeder are all `.php`/`.blade.php`. */
    public function supports(string $kind): bool;

    /**
     * @return array<string, mixed>
     */
    public function extract(SplFileInfo $file, string $source): array;
}
