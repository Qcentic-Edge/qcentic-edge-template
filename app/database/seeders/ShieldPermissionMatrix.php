<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class ShieldPermissionMatrix
{
    /**
     * Create Shield permission rows from the admin panel and attach them:
     * super_admin (all), user (View:ApiTokens when that page exists).
     */
    public static function seed(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        foreach (FilamentShield::getResources() ?? [] as $entity) {
            Utils::generateForResource($entity['resourceFqcn']);
        }

        foreach ([FilamentShield::getPages() ?? [], FilamentShield::getWidgets() ?? []] as $group) {
            foreach ($group as $entity) {
                foreach ($entity['permissions'] ?? [] as $key => $value) {
                    $name = is_array($value) ? (string) ($value['key'] ?? $key) : (is_int($key) ? (string) $value : (string) $key);
                    if ($name !== '') {
                        Utils::generateForPageOrWidget($name);
                    }
                }
            }
        }

        Utils::generateForExtraPermissions();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::findByName('super_admin')->syncPermissions(Permission::query()->get());
        Role::findByName('user')->syncPermissions(
            Permission::query()->whereIn('name', ['View:ApiTokens'])->get()
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
