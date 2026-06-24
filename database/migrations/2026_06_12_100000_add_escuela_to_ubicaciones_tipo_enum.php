<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;
        DB::statement("ALTER TABLE ubicaciones MODIFY COLUMN tipo ENUM('visita_cliente','propiedad','escuela') NOT NULL DEFAULT 'visita_cliente'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;
        DB::statement("UPDATE ubicaciones SET tipo = 'visita_cliente' WHERE tipo = 'escuela'");
        DB::statement("ALTER TABLE ubicaciones MODIFY COLUMN tipo ENUM('visita_cliente','propiedad') NOT NULL DEFAULT 'visita_cliente'");
    }
};
