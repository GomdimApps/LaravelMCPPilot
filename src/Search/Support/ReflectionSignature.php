<?php

namespace GomdimApps\LaravelMCPPilot\Search\Support;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;

/** Native reflection facts about a class/method — kind-agnostic, applies to every PHP entry. */
class ReflectionSignature
{
    /** @return array<string, mixed> */
    public function forClass(ReflectionClass $class): array
    {
        return array_filter([
            'extends' => ($parent = $class->getParentClass()) ? $parent->getName() : null,
            'implements' => $class->getInterfaceNames(),
        ], fn ($value) => $value !== [] && $value !== null);
    }

    /** @return array{name: string, params: array<int, array{name: string, type: ?string}>, returns: ?string} */
    public function forMethod(ReflectionMethod $method): array
    {
        return [
            'name' => $method->getName(),
            'params' => collect($method->getParameters())
                ->map(fn (ReflectionParameter $parameter) => [
                    'name' => $parameter->getName(),
                    'type' => $this->typeToString($parameter->getType()),
                ])
                ->all(),
            'returns' => $this->typeToString($method->getReturnType()),
        ];
    }

    /**
     * FQCN of every parameter type across a set of reflected methods that is itself a subclass
     * of $baseClass — e.g. finding which controller method injects a FormRequest.
     *
     * @param  Collection<int, ReflectionMethod>  $methods
     * @return list<string>
     */
    public function paramTypesSubclassing(Collection $methods, string $baseClass): array
    {
        return $methods
            ->flatMap(fn (ReflectionMethod $method) => $method->getParameters())
            ->map(fn (ReflectionParameter $parameter) => $parameter->getType())
            ->filter(fn (?ReflectionType $type) => $type instanceof ReflectionNamedType && ! $type->isBuiltin())
            ->map(fn (ReflectionNamedType $type) => $type->getName())
            ->filter(fn (string $type) => is_subclass_of($type, $baseClass))
            ->unique()
            ->values()
            ->all();
    }

    private function typeToString(?ReflectionType $type): ?string
    {
        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            $prefix = $type->allowsNull() && $type->getName() !== 'null' ? '?' : '';

            return $prefix.$type->getName();
        }

        $glue = $type instanceof ReflectionIntersectionType ? '&' : '|';

        return collect($type->getTypes())->map(fn (ReflectionNamedType $inner) => $inner->getName())->implode($glue);
    }
}
