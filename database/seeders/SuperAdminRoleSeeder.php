<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Configura el rol `super_admin` con todos los permisos en formato Shield v4.
 * Ejecutar después de `php artisan shield:generate --all --panel=admin --no-interaction`.
 *
 * Nota: define_via_gate: false en config/filament-shield.php, por lo tanto
 * super_admin requiere permisos explícitos (no tiene bypass automático).
 */
class SuperAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // Asignar todos los permisos v4 generados por shield:generate
        // (reconocibles por empezar con mayúscula, ej: "ViewAny:Contacto")
        $permisos = Permission::all()
            ->filter(fn ($p) => preg_match('/^[A-Z]/', $p->name))
            ->all();

        $superAdmin->syncPermissions($permisos);

        $this->command->info('super_admin: ' . count($permisos) . ' permisos asignados.');
    }
}
