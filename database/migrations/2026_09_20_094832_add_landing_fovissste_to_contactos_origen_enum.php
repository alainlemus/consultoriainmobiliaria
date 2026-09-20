<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar 'landing_fovissste' al ENUM de la columna 'origen' en contactos,
        // para distinguir los prospectos que llegan por la landing /fovissste
        // de los del sitio web general ('sitio_web').
        // MODIFY COLUMN con ENUM solo funciona en MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE contactos MODIFY COLUMN origen
                ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor','app_acreditado','landing_fovissste')
                DEFAULT NULL");
        }
        // SQLite no tiene ENUM nativo — usa TEXT y no requiere ALTER para ampliar valores
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE contactos SET origen = 'sitio_web' WHERE origen = 'landing_fovissste'");
            DB::statement("ALTER TABLE contactos MODIFY COLUMN origen
                ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor','app_acreditado')
                DEFAULT NULL");
        }
    }
};
