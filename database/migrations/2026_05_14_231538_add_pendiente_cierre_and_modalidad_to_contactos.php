<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar el ENUM para incluir pendiente_cierre y contrato_firmado
        DB::statement("ALTER TABLE contactos MODIFY COLUMN estado_prospecto ENUM(
            'nuevo',
            'contactado',
            'precalificado',
            'pendiente_cierre',
            'contrato_firmado',
            'convertido',
            'descartado'
        ) NOT NULL DEFAULT 'nuevo'");

        Schema::table('contactos', function (Blueprint $table) {
            $table->enum('modalidad_cierre', [
                'telefono',
                'cita_oficina',
                'visita_domicilio',
                'whatsapp',
            ])->nullable()->after('estado_prospecto');

            $table->text('notas_cierre')->nullable()->after('modalidad_cierre');
            $table->timestamp('fecha_envio_dueno')->nullable()->after('notas_cierre');
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn(['modalidad_cierre', 'notas_cierre', 'fecha_envio_dueno']);
        });

        DB::statement("ALTER TABLE contactos MODIFY COLUMN estado_prospecto ENUM(
            'nuevo',
            'contactado',
            'precalificado',
            'propuesta_enviada',
            'convertido',
            'descartado'
        ) NOT NULL DEFAULT 'nuevo'");
    }
};
