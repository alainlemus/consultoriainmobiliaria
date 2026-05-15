<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AsesorSeeder extends Seeder
{
    public function run(): void
    {
        $asesorRole = Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);

        $asesores = [
            ['name' => 'Ana López Hernández',   'email' => 'ana.lopez@consultoria.mx'],
            ['name' => 'Carlos Martínez Reyes', 'email' => 'carlos.martinez@consultoria.mx'],
            ['name' => 'María González Luna',   'email' => 'maria.gonzalez@consultoria.mx'],
            ['name' => 'Juan Ramírez Castillo', 'email' => 'juan.ramirez@consultoria.mx'],
            ['name' => 'Laura Torres Vega',     'email' => 'laura.torres@consultoria.mx'],
            ['name' => 'Roberto Sánchez Mora',  'email' => 'roberto.sanchez@consultoria.mx'],
            ['name' => 'Sofia Flores Díaz',     'email' => 'sofia.flores@consultoria.mx'],
            ['name' => 'Miguel Díaz Ruiz',       'email' => 'miguel.diaz@consultoria.mx'],
            ['name' => 'Patricia Cruz Lima',    'email' => 'patricia.cruz@consultoria.mx'],
            ['name' => 'Fernando Morales Poe',  'email' => 'fernando.morales@consultoria.mx'],
        ];

        foreach ($asesores as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                    'activo'   => true,
                ]
            );

            if (! $user->hasRole('asesor')) {
                $user->assignRole($asesorRole);
            }
        }

        $this->assignPermisosAsesor($asesorRole);
    }

    private function assignPermisosAsesor(Role $asesorRole): void
    {
        try {
            $permisos = Permission::pluck('name')->toArray();

            if (count($permisos) === 0) {
                return;
            }

            $asesorPermissions = array_filter($permisos, function ($permiso) {
                // Contactos: full access menos delete
                if (str_starts_with($permiso, 'contacto') && ! str_contains($permiso, '_any') && ! str_contains($permiso, 'force_') && ! str_contains($permiso, 'replicate') && ! str_contains($permiso, 'reorder')) {
                    return true;
                }
                if ($permiso === 'view_any_contacto') {
                    return true;
                }
                // delete_any para contactos - no
                if (in_array($permiso, ['delete_contacto', 'delete_any_contacto', 'force_delete_contacto', 'force_delete_any_contacto'])) {
                    return false;
                }
                // Expedientes: solo view y view_any
                if (str_starts_with($permiso, 'expediente') && (str_starts_with($permiso, 'view_expediente') || $permiso === 'view_any_expediente')) {
                    return true;
                }
                // Page del panel de asesor
                if ($permiso === 'page_PanelAsesores') {
                    return true;
                }
                return false;
            });

            $asesorRole->syncPermissions(array_values($asesorPermissions));

        } catch (\Throwable $e) {
            // Los permisos se generan con shield:generate después del primer seed
        }
    }
}