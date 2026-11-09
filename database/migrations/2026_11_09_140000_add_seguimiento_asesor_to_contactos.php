<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seguimiento del asesor sobre el prospecto:
 *  - contactado_at: cuándo dejó de estar en «nuevo» (tiempo de respuesta).
 *  - alerta_sin_atender_at: última alerta por prospecto sin atender.
 *  - asesor_conflicto_id / conflicto_detectado_at: el acreditado llegó con el
 *    QR de otro asesor y el prospecto ya tenía uno; admin decide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->timestamp('contactado_at')->nullable()->after('estado_prospecto');
            $table->timestamp('alerta_sin_atender_at')->nullable()->after('contactado_at');
            $table->foreignId('asesor_conflicto_id')->nullable()->after('codigo_qr_id')->constrained('users')->nullOnDelete();
            $table->timestamp('conflicto_detectado_at')->nullable()->after('asesor_conflicto_id');
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asesor_conflicto_id');
            $table->dropColumn(['contactado_at', 'alerta_sin_atender_at', 'conflicto_detectado_at']);
        });
    }
};
