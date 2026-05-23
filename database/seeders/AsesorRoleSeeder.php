<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Configura el rol `asesor` con permisos formato Shield v4 (PascalCase con separador :).
 * Ejecutar después de `php artisan shield:generate --all --panel=admin --no-interaction`.
 */
class AsesorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $asesor = Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);

        // Shield v4 usa formato PascalCase: "Accion:Modelo"
        $permisos = [
            // Dashboard y páginas del asesor
            'View:DashboardAsesor',
            'View:MiPerfil',
            'View:SimuladorPrecalificacion',

            // Contactos (prospectos): ver y editar solo los suyos — sin eliminar
            'ViewAny:Contacto', 'View:Contacto',
            'Create:Contacto',  'Update:Contacto',

            // Expedientes: crear desde prospecto + ver y editar los asignados
            'ViewAny:Expediente', 'View:Expediente',
            'Create:Expediente',  'Update:Expediente',

            // Comisiones: solo lectura
            'ViewAny:Comision', 'View:Comision',

            // Documentos del expediente: ver y subir archivos
            'ViewAny:DocumentoRequerido', 'View:DocumentoRequerido',
            'Create:DocumentoRequerido',  'Update:DocumentoRequerido',
        ];

        // Asegurar que todos los permisos existan antes de asignar
        // (shield:generate debería haberlos creado; esto es un fallback)
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $asesor->syncPermissions($permisos);
    }
}
