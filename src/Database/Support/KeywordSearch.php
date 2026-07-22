<?php

namespace GomdimApps\LaravelMCPPilot\Database\Support;

use Illuminate\Support\Str;

/** Pure case-insensitive substring match — no I/O, safe to reuse standalone. */
class KeywordSearch
{
    public function matches(string $keyword, string $haystack): bool
    {
        return Str::contains(Str::lower($haystack), Str::lower($keyword));
    }
}
