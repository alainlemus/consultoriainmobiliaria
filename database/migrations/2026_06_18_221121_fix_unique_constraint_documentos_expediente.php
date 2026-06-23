<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            // En MySQL el índice existe; en SQLite no (no se creó en la migración anterior)
            if (DB::getDriverName() === 'mysql') {
                $table->dropUnique('documentos_expediente_tipo_seccion_unique');
            }

            $table->unique(
                ['expediente_id', 'tipo', 'seccion', 'categoria'],
                'documentos_expediente_tipo_seccion_categoria_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropUnique('documentos_expediente_tipo_seccion_categoria_unique');
            $table->unique(
                ['expediente_id', 'tipo', 'seccion'],
                'documentos_expediente_tipo_seccion_unique'
            );
        });
    }
};
