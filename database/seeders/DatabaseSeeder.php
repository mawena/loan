<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Utilisateur administrateur par défaut (rôle "admin" = is_super_admin).
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );

        $admin->forceFill([
            'password' => Hash::make('password'),
            'activated' => true,
            'password_change_required' => false,
        ])->save();

        $admin->assignRole('admin');
    }
}
