<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use GomdimApps\LaravelMCPPilot\Search\Contracts\SupportSchemaExtractor;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Deliberate, scoped exception to this module's "never execute source" rule: a config file is, by
 * unbreakable Laravel convention, a pure `<?php return [...];` literal with no side effects, and
 * File::getRequire() is the exact mechanism Laravel's own ConfigServiceProvider uses to load every
 * config file on every request — this introduces no execution risk beyond the app booting at all.
 */
class ConfigSchema implements SupportSchemaExtractor
{
    public function supports(string $kind): bool
    {
        return $kind === 'config';
    }

    /** One entry per file with a flattened key list — Tokenizer::termsFor() already makes every key searchable, without one entry per key. */
    public function extract(SplFileInfo $file, string $source): array
    {
        $values = rescue(fn () => File::getRequire($file->getPathname()), rescue: null, report: false);

        return is_array($values) ? array_filter(['keys' => array_keys(Arr::dot($values))]) : [];
    }
}
