<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the first Super Admin from CURE_ADMIN_* in .env. If no password is set a random
 * one is generated and printed once. Nothing is created if a Super Admin already exists.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::role('Super Admin')->exists()) {
            return;
        }

        $email = env('CURE_ADMIN_EMAIL', 'owner@example.com');
        $password = env('CURE_ADMIN_PASSWORD') ?: Str::password(16, true, true, true, false);
        $generated = ! env('CURE_ADMIN_PASSWORD');

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => env('CURE_ADMIN_NAME', 'Owner'),
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();
        $user->syncRoles(['Super Admin']);

        $this->command?->info("Super Admin created: {$email}");
        if ($generated) {
            $this->command?->warn("Temporary password (shown once): {$password}");
        }
        $this->command?->warn('Change the password after the first sign-in (Profile > Password).');
    }
}
