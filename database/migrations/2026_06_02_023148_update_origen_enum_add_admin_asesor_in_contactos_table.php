<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN con ENUM solo funciona en MySQL/MariaDB
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE contactos MODIFY COLUMN origen ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor','app_acreditado') NULL");
        }
        // SQLite no tiene ENUM nativo — usa TEXT y no requiere ALTER para ampliar valores
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE contactos SET origen = 'otro' WHERE origen IN ('admin','asesor','app_acreditado')");
            DB::statement("ALTER TABLE contactos MODIFY COLUMN origen ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil') NULL");
        }
    }
};
