<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca cuándo el equipo del panel terminó de capturar los datos de un documento
 * subido desde la app. Es la única marca de avance que queda: un documento con
 * archivo y sin capturado_at está "por capturar".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->timestamp('capturado_at')->nullable()->after('ruta_archivo');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropColumn('capturado_at');
        });
    }
};
