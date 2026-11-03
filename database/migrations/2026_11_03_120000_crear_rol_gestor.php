<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rol `gestor`: la persona que hace los trámites en las dependencias públicas
 * (Registro Público, Catastro, etc.). Recibe expedientes que le envía la
 * administración, descarga sus documentos y sube los que obtiene.
 *
 * Se crea por migración (y no solo por seeder) para que exista en producción
 * sin correr seeders. Por ahora no tiene permisos de Shield: solo entra a su
 * propia página del panel (PanelGestor), que valida el rol directamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Solo se borra si nadie lo tiene asignado, para no dejar usuarios sin rol.
        $rol = Role::where('name', 'gestor')->where('guard_name', 'web')->first();

        if ($rol && $rol->users()->doesntExist()) {
            $rol->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
