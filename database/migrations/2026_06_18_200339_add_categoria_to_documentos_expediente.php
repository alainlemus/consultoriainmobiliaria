<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            // Carpeta origen de la carga masiva: ACREDITADA, VENDEDOR, VIVIENDA, SOFOM, NOTARIA, etc.
            // null = documento del checklist estándar (cargado individualmente)
            $table->string('categoria', 100)->nullable()->after('seccion');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
