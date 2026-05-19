<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles si no existen
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );
        Role::firstOrCreate(
            ['name' => 'asesor', 'guard_name' => 'web']
        );

        // Crear usuario admin
        $user = User::firstOrCreate(
            ['email' => 'antonio@consultoriainmobiliaria.com.mx'],
            [
                'name'     => 'Jose Antonio Solis Santuario',
                'password' => Hash::make('password'),
                'activo'   => true,
            ]
        );

        // Asignar rol super_admin
        if (! $user->hasRole('super_admin')) {
            $user->assignRole($superAdmin);
        }

        // Asignar todos los permisos al rol super_admin (no al usuario directamente)
        $permissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        if (count($permissions) > 0) {
            $superAdmin->syncPermissions($permissions);
        }

        // Garantizar permisos de las páginas API Móvil (pueden no estar en Shield aún)
        $apiPermisos = [
            'page_ApiConfiguracion',
            'page_ApiDocumentacion',
            'page_ApiMonitor',
        ];
        foreach ($apiPermisos as $permiso) {
            $perm = Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            if (! $superAdmin->hasPermissionTo($perm)) {
                $superAdmin->givePermissionTo($perm);
            }
        }
    }
}
