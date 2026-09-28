<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;

/** Native: Gate::policies() (model FQCN => policy FQCN). Doesn't cover a policy only auto-discovered, not yet resolved. */
class PolicyKind implements PhpKindDetector
{
    public function __construct(private readonly string $policyNamespace) {}

    public function supports(ReflectionClass $class): bool
    {
        return $this->modelsFor($class->getName()) !== []
            || str_contains($class->getName(), $this->policyNamespace);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'policy';
    }

    public function context(ReflectionClass $class): array
    {
        return array_filter(['policy_for' => $this->modelsFor($class->getName())]);
    }

    private function modelsFor(string $class): array
    {
        return collect(Gate::policies())
            ->filter(fn (string $policy) => $policy === $class)
            ->keys()
            ->values()
            ->all();
    }
}
