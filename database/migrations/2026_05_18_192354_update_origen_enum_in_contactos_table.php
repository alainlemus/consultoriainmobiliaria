<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite no tiene ENUM — solo aplica en MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE contactos MODIFY COLUMN origen ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil') NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE contactos SET origen = 'otro' WHERE origen = 'app_movil'");
        DB::statement("ALTER TABLE contactos MODIFY COLUMN origen ENUM('sitio_web','campo','referido','whatsapp','otro') NULL");
    }
};
