<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use GomdimApps\LaravelMCPPilot\Search\Contracts\FrontendSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\AnchorExtractor;
use Symfony\Component\Finder\SplFileInfo;

class VueComponentSchema implements FrontendSchemaExtractor
{
    public function __construct(private readonly AnchorExtractor $anchor) {}

    public function supports(SplFileInfo $file): bool
    {
        return $file->getExtension() === 'vue';
    }

    public function extract(string $source): array
    {
        $script = $this->scriptContent($source);

        return array_filter([
            'props' => $this->fields($script, 'defineProps'),
            'emits' => $this->fields($script, 'defineEmits'),
            'form_fields' => $this->fields($script, 'useForm'),
        ], fn (array $value) => $value !== []);
    }

    /**
     * <script setup> content only — defineProps/defineEmits/useForm in <template> or comments
     * would otherwise produce false matches.
     */
    private function scriptContent(string $source): string
    {
        foreach (['/<script\b[^>]*\bsetup\b[^>]*>(.*?)<\/script>/s', '/<script\b[^>]*>(.*?)<\/script>/s'] as $pattern) {
            if (preg_match($pattern, $source, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }

    /**
     * Covers both defineProps call forms: the type-literal generic (`defineProps<{ ... }>()`)
     * and the runtime-options call (`defineProps([...])` / `defineEmits([...])` / `useForm({...})`).
     */
    private function fields(string $script, string $anchor): array
    {
        $body = $this->anchor->balanced($script, preg_quote($anchor, '/').'\s*<\s*', '{', '}')
            ?? $this->anchor->balanced($script, preg_quote($anchor, '/').'\s*', '(', ')');

        return $body ? $this->anchor->fieldNames($body) : [];
    }
}
