<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles si no existen
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );
        Role::firstOrCreate(
            ['name' => 'panel_user', 'guard_name' => 'web']
        );

        // Crear usuario admin
        $user = User::firstOrCreate(
            ['email' => 'admin@consultoria.mx'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
                'activo'   => true,
            ]
        );

        // Asignar rol super_admin
        if (! $user->hasRole('super_admin')) {
            $user->assignRole($superAdmin);
        }
    }
}
