<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Rol `gestor` (trámites en dependencias públicas). En producción lo crea la
 * migración 2026_11_03_120000_crear_rol_gestor; aquí se asegura en instalaciones
 * nuevas que se arman con seeders.
 */
class GestorRoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    }
}
