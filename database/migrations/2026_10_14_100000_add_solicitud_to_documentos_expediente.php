<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento faltante solicitado por un administrador al acreditado: queda como un documento
 * pendiente sin archivo, con la nota que explica cuál falta. Mientras esté pendiente el
 * expediente no se considera completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->timestamp('solicitado_at')->nullable()->after('motivo_rechazo');
            $table->foreignId('solicitado_por')->nullable()->after('solicitado_at')->constrained('users')->nullOnDelete();
            $table->text('solicitud_nota')->nullable()->after('solicitado_por');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solicitado_por');
            $table->dropColumn(['solicitado_at', 'solicitud_nota']);
        });
    }
};
