<?php

namespace GomdimApps\LaravelMCPPilot\Search\Kind;

use Closure;
use GomdimApps\LaravelMCPPilot\Search\Contracts\PhpKindDetector;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use ReflectionClass;

/** Native: Event::getRawListeners() (event => [listeners]). Auto-discovered listeners populate this at boot. */
class ListenerKind implements PhpKindDetector
{
    public function __construct(private readonly string $listenerNamespace) {}

    public function supports(ReflectionClass $class): bool
    {
        return $this->eventsFor($class->getName()) !== []
            || str_contains($class->getName(), $this->listenerNamespace);
    }

    public function kind(ReflectionClass $class): string
    {
        return 'listener';
    }

    public function context(ReflectionClass $class): array
    {
        return array_filter(['listens_to' => $this->eventsFor($class->getName())]);
    }

    private function eventsFor(string $class): array
    {
        return collect(Event::getRawListeners())
            ->filter(fn (array $listeners) => collect($listeners)->contains(fn ($listener) => $this->listenerClass($listener) === $class))
            ->keys()
            ->values()
            ->all();
    }

    private function listenerClass(mixed $listener): ?string
    {
        return match (true) {
            $listener instanceof Closure => null,
            is_array($listener) => $listener[0] ?? null,
            is_string($listener) && str_contains($listener, '@') => Str::before($listener, '@'),
            is_string($listener) => $listener,
            default => null,
        };
    }
}
