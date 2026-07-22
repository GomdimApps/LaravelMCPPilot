<?php

namespace GomdimApps\LaravelMCPPilot\Database\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Optional native integration with spatie/laravel-permission — a no-op (isAvailable() === false,
 * map() === null) whenever the package isn't installed in the consuming app. Never a hard dependency:
 * the `use` imports above only ever resolve to bare FQCN strings unless isAvailable() is true first.
 */
class SpatiePermissionIntrospector
{
    private const TABLES = ['roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions'];

    public function __construct(private readonly bool $enabled = true) {}

    public function isAvailable(): bool
    {
        return $this->enabled && class_exists(Role::class);
    }

    /** Resolves the Spatie permission graph via Eloquent instead of hand-joining the pivot tables. */
    public function map(string $connection, string $table): ?array
    {
        if (! $this->isAvailable() || ! in_array($table, self::TABLES, true)) {
            return null;
        }

        // Respects a consuming app's own model swap (`permission.models.role`/`.permission`)
        // instead of hardcoding Spatie's default model classes.
        $roleClass = config('permission.models.role', Role::class);
        $permissionClass = config('permission.models.permission', Permission::class);

        return [
            'roles' => $this->namedWithRelated($roleClass::on($connection)->with('permissions')->get(), 'permissions'),
            'permissions' => $this->namedWithRelated($permissionClass::on($connection)->with('roles')->get(), 'roles'),
        ];
    }

    private function namedWithRelated(Collection $items, string $relation): array
    {
        return $items->map(fn ($item) => [
            'name' => $item->name,
            'guard_name' => $item->guard_name,
            $relation => $item->{$relation}->pluck('name')->all(),
        ])->all();
    }
}
