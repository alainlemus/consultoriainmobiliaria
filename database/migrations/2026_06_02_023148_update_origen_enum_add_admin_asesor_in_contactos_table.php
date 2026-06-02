<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE contactos MODIFY COLUMN origen ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor') NULL");
    }

    public function down(): void
    {
        // Revert valores no soportados antes de reducir el ENUM
        DB::statement("UPDATE contactos SET origen = 'otro' WHERE origen IN ('admin','asesor')");
        DB::statement("ALTER TABLE contactos MODIFY COLUMN origen ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil') NULL");
    }
};
