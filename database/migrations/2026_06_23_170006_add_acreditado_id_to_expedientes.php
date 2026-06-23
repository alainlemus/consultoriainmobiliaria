<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar columna si no existe (SQLite puede haberla creado parcialmente)
        if (! Schema::hasColumn('expedientes', 'acreditado_id')) {
            Schema::table('expedientes', function (Blueprint $table) {
                $table->unsignedBigInteger('acreditado_id')->nullable()->after('contacto_id');
            });
        }

        // FK solo en MySQL (SQLite no la soporta con ALTER TABLE de la misma forma)
        if (DB::getDriverName() === 'mysql') {
            // Verificar que la FK no exista ya
            $fks = DB::select("
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'expedientes'
                AND COLUMN_NAME = 'acreditado_id'
                AND REFERENCED_TABLE_NAME = 'acreditados'
                AND TABLE_SCHEMA = DATABASE()
            ");
            if (empty($fks)) {
                DB::statement('ALTER TABLE expedientes ADD CONSTRAINT expedientes_acreditado_id_foreign FOREIGN KEY (acreditado_id) REFERENCES acreditados(id) ON DELETE SET NULL');
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE expedientes DROP FOREIGN KEY expedientes_acreditado_id_foreign');
            } catch (\Throwable) {}
        }

        if (Schema::hasColumn('expedientes', 'acreditado_id')) {
            Schema::table('expedientes', function (Blueprint $table) {
                $table->dropColumn('acreditado_id');
            });
        }
    }
};
