<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El gestor avisa con «Ya terminé» que entregó todo; el encargo queda en
 * `por_validar` hasta que la administración valide. Estado es string, así que
 * solo hacen falta las fechas: cuándo entregó y cuándo se recordó al admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encargos_gestor', function (Blueprint $table) {
            $table->timestamp('entregado_at')->nullable()->after('tramite_iniciado_at');
            $table->timestamp('ultimo_recordatorio_at')->nullable()->after('entregado_at');
        });
    }

    public function down(): void
    {
        Schema::table('encargos_gestor', function (Blueprint $table) {
            $table->dropColumn(['entregado_at', 'ultimo_recordatorio_at']);
        });
    }
};
