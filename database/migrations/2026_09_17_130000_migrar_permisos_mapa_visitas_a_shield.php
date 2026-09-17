<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Migra el acceso a Mapa de Visitas, Rutas de Asesores y Anuncios de checks
 * hardcodeados por nombre de rol a permisos dinámicos de Shield.
 *
 * Solo AGREGA permisos (givePermissionTo, nunca sync) a los roles existentes
 * para preservar exactamente el acceso que ya tenían antes de este cambio:
 *   - asesor: ve su propio Mapa de Visitas y administra sus propios anuncios.
 *   - super_admin: ya tiene todos los permisos, aquí solo se asegura el nuevo
 *     permiso personalizado "Ver:TodosLosAsesores".
 *   - Revisor de Rutas: rol de solo lectura, ve los datos de todos los asesores
 *     en Mapa de Visitas, Rutas de Asesores y Anuncios (sin poder editarlos).
 */
return new class extends Migration
{
    public function up(): void
    {
        $guard = 'web';

        foreach ([
            'View:MapaVisitas',
            'View:RutaAsesorPage',
            'ViewAny:Anuncio', 'View:Anuncio', 'Update:Anuncio',
            'Ver:TodosLosAsesores',
        ] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => $guard]);
        }

        if ($asesor = Role::where('name', 'asesor')->first()) {
            $asesor->givePermissionTo([
                'View:MapaVisitas',
                'ViewAny:Anuncio', 'View:Anuncio', 'Update:Anuncio',
            ]);
        }

        if ($superAdmin = Role::where('name', 'super_admin')->first()) {
            $superAdmin->givePermissionTo(['Ver:TodosLosAsesores']);
        }

        if ($revisorRutas = Role::where('name', 'Revisor de Rutas')->first()) {
            $revisorRutas->givePermissionTo([
                'View:MapaVisitas',
                'View:RutaAsesorPage',
                'ViewAny:Anuncio', 'View:Anuncio',
                'Ver:TodosLosAsesores',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No se revierte: quitar estos permisos podría dejar sin acceso a
        // roles que un administrador haya reconfigurado manualmente después
        // de aplicar esta migración.
    }
};
