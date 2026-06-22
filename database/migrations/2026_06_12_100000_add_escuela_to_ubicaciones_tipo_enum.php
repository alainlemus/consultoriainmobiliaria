<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ubicaciones MODIFY COLUMN tipo ENUM('visita_cliente','propiedad','escuela') NOT NULL DEFAULT 'visita_cliente'");
    }

    public function down(): void
    {
        // Convierte los registros escuela a visita_cliente antes de eliminar el valor del enum
        DB::statement("UPDATE ubicaciones SET tipo = 'visita_cliente' WHERE tipo = 'escuela'");
        DB::statement("ALTER TABLE ubicaciones MODIFY COLUMN tipo ENUM('visita_cliente','propiedad') NOT NULL DEFAULT 'visita_cliente'");
    }
};
