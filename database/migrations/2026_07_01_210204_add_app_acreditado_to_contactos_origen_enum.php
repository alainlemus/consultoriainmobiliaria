<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar 'app_acreditado' al ENUM de la columna 'origen' en contactos.
        // MySQL requiere redeclara todos los valores existentes al modificar un ENUM.
        DB::statement("ALTER TABLE contactos MODIFY COLUMN origen
            ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor','app_acreditado')
            DEFAULT NULL");
    }

    public function down(): void
    {
        // Revertir — quitar 'app_acreditado' (los registros con ese valor quedarán como NULL)
        DB::statement("ALTER TABLE contactos MODIFY COLUMN origen
            ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor')
            DEFAULT NULL");
    }
};

