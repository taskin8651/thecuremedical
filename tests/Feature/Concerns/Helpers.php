<?php

namespace Tests\Feature\Concerns;

use App\Models\Product;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

trait Helpers
{
    protected const PASSWORD = 'Str0ng-Passw0rd!';

    protected function seedRoles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function staff(string $role, array $attrs = []): User
    {
        $this->seedRoles();
        $user = User::query()->create($attrs + [
            'name' => $role.' User', 'email' => strtolower(str_replace(' ', '', $role)).'@example.test',
            'password' => Hash::make(self::PASSWORD), 'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function product(array $attrs = []): Product
    {
        return Product::query()->create($attrs + ['name' => 'Test Product '.uniqid(), 'is_active' => true, 'track_inventory' => true, 'mrp' => 100]);
    }

    protected function enable(string ...$flags): void
    {
        foreach ($flags as $f) {
            Settings::set($f.'_enabled', '1');
        }
        Settings::flush();
    }
}
