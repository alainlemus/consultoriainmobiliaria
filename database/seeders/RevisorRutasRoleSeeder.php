<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Configura el rol `Revisor de Rutas`: solo lectura sobre el Mapa de Visitas,
 * las Rutas de Asesores y los Anuncios, viendo los datos de todos los asesores.
 * Ejecutar después de `php artisan shield:generate --all --panel=admin --no-interaction`.
 */
class RevisorRutasRoleSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Role::firstOrCreate(['name' => 'Revisor de Rutas', 'guard_name' => 'web']);

        $permisos = [
            'View:MapaVisitas',
            'View:RutaAsesorPage',
            'ViewAny:Anuncio', 'View:Anuncio',
            // Ve los datos de todos los asesores en vez de solo los propios.
            'Ver:TodosLosAsesores',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $rol->syncPermissions($permisos);
    }
}
