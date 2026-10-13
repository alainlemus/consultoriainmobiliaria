<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Validación de documentos: solo un administrador los da por buenos (validado_at) o
 * los rechaza con un motivo. El motivo vive aparte de `notas`, que identifica el documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->timestamp('validado_at')->nullable()->after('capturado_at');
            $table->foreignId('validado_por')->nullable()->after('validado_at')->constrained('users')->nullOnDelete();
            $table->text('motivo_rechazo')->nullable()->after('validado_por');
        });

        // Los rechazos anteriores guardaban el motivo en `notas`: se pasa al campo propio.
        DB::table('documentos_expediente')
            ->where('estado', 'rechazado')
            ->update(['motivo_rechazo' => DB::raw('notas'), 'notas' => null]);
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validado_por');
            $table->dropColumn(['validado_at', 'motivo_rechazo']);
        });
    }
};
