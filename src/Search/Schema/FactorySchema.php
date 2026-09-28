<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use Illuminate\Support\Str;
use GomdimApps\LaravelMCPPilot\Search\Contracts\SupportSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\UseImportResolver;
use Symfony\Component\Finder\SplFileInfo;

class FactorySchema implements SupportSchemaExtractor
{
    public function __construct(private readonly UseImportResolver $imports) {}

    public function supports(string $kind): bool
    {
        return $kind === 'factory';
    }

    public function extract(SplFileInfo $file, string $source): array
    {
        $short = preg_match('/protected\s+\$model\s*=\s*([A-Za-z0-9_\\\\]+)::class/', $source, $matches)
            ? $matches[1]
            : Str::beforeLast($file->getFilenameWithoutExtension(), 'Factory');

        $model = $this->imports->resolve($short, $source);

        return array_filter([
            'model' => $model,
            'relations' => [['type' => 'uses', 'target' => $model]],
        ]);
    }
}
