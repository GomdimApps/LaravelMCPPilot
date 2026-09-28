<?php

namespace GomdimApps\LaravelMCPPilot\Search\Schema;

use GomdimApps\LaravelMCPPilot\Search\Contracts\SupportSchemaExtractor;
use GomdimApps\LaravelMCPPilot\Search\Support\UseImportResolver;
use Symfony\Component\Finder\SplFileInfo;

class SeederSchema implements SupportSchemaExtractor
{
    public function __construct(private readonly UseImportResolver $imports) {}

    public function supports(string $kind): bool
    {
        return $kind === 'seeder';
    }

    public function extract(SplFileInfo $file, string $source): array
    {
        $models = $this->models($source);

        return array_filter([
            'models' => $models,
            'relations' => collect($models)->map(fn (string $model) => ['type' => 'seeds', 'target' => $model])->all(),
        ]);
    }

    /** Deliberately scoped to Model::method( calls — DB::table('x')->insert(...) is a different idiom (a table string, not a model). */
    private function models(string $source): array
    {
        preg_match_all('/\b([A-Z][A-Za-z0-9_\\\\]*)::(?:factory|create|insert|firstOrCreate|updateOrCreate)\s*\(/', $source, $matches);

        return collect($matches[1])
            ->map(fn (string $short) => $this->imports->resolve($short, $source))
            ->unique()
            ->values()
            ->all();
    }
}
