<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Columna de resultado de la precalificación
        Schema::table('contactos', function (Blueprint $table) {
            $table->string('resultado_precalificacion')->nullable()
                  ->after('notas_precalificacion')
                  ->comment('Resultado de la precalificación: pendiente, aprobado, condicional, no_califica');
        });

        // Extender el enum de estado_prospecto para incluir 'precalificado' si no existe
        // (ya existe desde la migración anterior, pero nos aseguramos)
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn('resultado_precalificacion');
        });
    }
};
