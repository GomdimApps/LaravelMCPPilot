<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use GomdimApps\LaravelMCPPilot\Search\Contracts\SupportSchemaExtractor;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Plain regex over the raw .blade.php source, not BladeCompiler::compileString(): @extends and
 * @include compile to the same call shape, so recovering "which directive was this" from compiled
 * output needs more work than just matching the directive that's already unambiguous in the raw
 * source — the same reasoning VueComponentSchema/TypeScriptSchema already use.
 */
class BladeViewSchema implements SupportSchemaExtractor
{
    public function supports(string $kind): bool
    {
        return $kind === 'view';
    }

    public function extract(SplFileInfo $file, string $source): array
    {
        $extends = preg_match('/@extends\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches) ? $matches[1] : null;
        $includes = $this->matchAll('/@include(?:If|When|Unless)?\(\s*[\'"]([^\'"]+)[\'"]/', $source);
        $components = $this->matchAll('/@component\(\s*[\'"]([^\'"]+)[\'"]/', $source);
        $sections = $this->matchAll('/@section\(\s*[\'"]([^\'"]+)[\'"]/', $source);

        $relations = [
            ...($extends ? [['type' => 'extends_view', 'target' => $extends]] : []),
            ...collect([...$includes, ...$components])->map(fn (string $target) => ['type' => 'includes_view', 'target' => $target])->all(),
        ];

        return array_filter([
            'extends' => $extends,
            'includes' => $includes,
            'components' => $components,
            'sections' => $sections,
            'relations' => $relations,
        ]);
    }

    private function matchAll(string $pattern, string $source): array
    {
        preg_match_all($pattern, $source, $matches);

        return array_values(array_unique($matches[1]));
    }
}
