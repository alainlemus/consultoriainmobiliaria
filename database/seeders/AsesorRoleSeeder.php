<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AsesorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $asesor = Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);

        $permisos = [
            // Dashboard y páginas del asesor (Shield los bloquea sin estos permisos)
            'page_DashboardAsesor',
            'page_MiPerfil',

            // Contactos (prospectos): ver y editar solo los suyos — sin eliminar
            'view_any_contacto', 'view_contacto',
            'create_contacto',   'update_contacto',

            // Expedientes: crear desde prospecto + ver y editar los asignados
            'view_any_expediente', 'view_expediente',
            'create_expediente',   'update_expediente',

            // Comisiones: solo lectura
            'view_any_comision', 'view_comision',

            // Documentos del expediente: ver y subir archivos
            'view_any_documento::requerido', 'view_documento::requerido',
            'update_documento::requerido',   'create_documento::requerido',
        ];

        // Asegurar que todos los permisos existan antes de asignar
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $asesor->syncPermissions($permisos);
    }
}
