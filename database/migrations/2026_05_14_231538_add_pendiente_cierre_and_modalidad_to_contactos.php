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
        // MODIFY COLUMN solo aplica en MySQL/MariaDB; SQLite usa TEXT y no valida ENUMs
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE contactos MODIFY COLUMN estado_prospecto ENUM(
                'nuevo',
                'contactado',
                'precalificado',
                'pendiente_cierre',
                'contrato_firmado',
                'convertido',
                'descartado'
            ) NOT NULL DEFAULT 'nuevo'");
        }

        Schema::table('contactos', function (Blueprint $table) {
            if (! Schema::hasColumn('contactos', 'modalidad_cierre')) {
                $table->string('modalidad_cierre')->nullable()->after('estado_prospecto');
            }
            if (! Schema::hasColumn('contactos', 'notas_cierre')) {
                $table->text('notas_cierre')->nullable()->after('modalidad_cierre');
            }
            if (! Schema::hasColumn('contactos', 'fecha_envio_dueno')) {
                $table->timestamp('fecha_envio_dueno')->nullable()->after('notas_cierre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn(['modalidad_cierre', 'notas_cierre', 'fecha_envio_dueno']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE contactos MODIFY COLUMN estado_prospecto ENUM(
                'nuevo',
                'contactado',
                'precalificado',
                'propuesta_enviada',
                'convertido',
                'descartado'
            ) NOT NULL DEFAULT 'nuevo'");
        }
    }
};
