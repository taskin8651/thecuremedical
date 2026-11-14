<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Builds the permission list and the six default roles from config/cure.php. Safe to re-run. */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [];
        foreach (config('cure.modules') as $module => [, $actions]) {
            foreach ($actions as $action) {
                $name = $module.'.'.$action;
                Permission::findOrCreate($name, 'web');
                $all[] = $name;
            }
        }

        // Super Admin also passes every check through Gate::before(), but holds the permissions too.
        Role::findOrCreate('Super Admin', 'web')->syncPermissions($all);

        foreach (config('cure.roles') as $roleName => $modules) {
            $perms = [];
            foreach ($modules as $module => $actions) {
                foreach ($actions as $action) {
                    $perms[] = $module.'.'.$action;
                }
            }
            $role = Role::findOrCreate($roleName, 'web');
            // Only seed the defaults the first time, so changes made in the admin panel are kept.
            if ($role->wasRecentlyCreated || $role->permissions()->count() === 0) {
                $role->syncPermissions(array_values(array_intersect($perms, $all)));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
