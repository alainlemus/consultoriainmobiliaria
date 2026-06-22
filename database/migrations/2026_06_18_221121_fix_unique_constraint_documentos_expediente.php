<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            // Quitar el unique anterior que no incluía categoria
            $table->dropUnique('documentos_expediente_tipo_seccion_unique');

            // Nuevo unique que incluye categoria:
            // permite INE en ACREDITADA y también INE en SOFOM/ACREDITADA
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
