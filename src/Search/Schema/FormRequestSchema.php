<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use Illuminate\Foundation\Http\FormRequest;
use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;

class FormRequestSchema implements PhpSchemaExtractor
{
    public function __construct(private readonly AnchorExtractor $anchor) {}

    public function supports(string $class): bool
    {
        return is_subclass_of($class, FormRequest::class);
    }

    public function extract(string $class, string $source): array
    {
        return array_filter([
            'rules' => $this->parseRules($this->anchor->balanced($source, 'function\s+rules\s*\([^)]*\)[^{]*\{.*?return\s+', '[', ']')),
            'authorize' => $this->parseAuthorize($source),
        ]);
    }

    /**
     * Rule values are kept as raw source text, not parsed into JSON: real rules mix plain
     * strings, arrays, and PHP expressions (e.g. "'unique:users,email,'.$userId"), so anything
     * short of executing the file would silently mangle the less common cases.
     */
    private function parseRules(?string $body): array
    {
        if (! $body) {
            return [];
        }

        preg_match_all('/^\s*[\'"]([^\'"]+)[\'"]\s*=>\s*(.+?),?\s*$/m', $body, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $rule) => [$rule[1] => $rule[2]])->all();
    }

    private function parseAuthorize(string $source): ?string
    {
        if (! preg_match('/function\s+authorize\s*\([^)]*\)[^{]*\{.*?return\s+(.+?);/s', $source, $matches)) {
            return null;
        }

        return preg_match('/(can\([^)]*\))/', $matches[1], $can) ? $can[1] : trim($matches[1]);
    }
}
