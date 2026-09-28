<?php

namespace GomdimApps\LaravelMCPPilot\Search\Support;

use Illuminate\Support\Str;

/** Resolves a short class name to its FQCN via the file's own `use` imports; never guesses. */
class UseImportResolver
{
    public function resolve(string $short, string $source): string
    {
        $short = ltrim($short, '\\');

        if (str_contains($short, '\\')) {
            return $short; // already fully qualified inline, e.g. \App\Models\Thing::create(...)
        }

        preg_match_all('/^use\s+([^;]+);/m', $source, $uses);

        foreach ($uses[1] as $import) {
            $import = trim($import);

            if (Str::afterLast($import, '\\') === $short) {
                return $import;
            }
        }

        return $short; // unresolved: same-namespace reference, or a genuinely unknown class
    }
}
