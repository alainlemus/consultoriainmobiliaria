<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            // ── Modalidad del crédito ─────────────────────────────────────────
            // individual | mancomunado
            $table->string('modalidad_credito')->nullable()->after('uso_credito')
                  ->comment('individual | mancomunado');

            // ── Banco participante (Para Todos) ───────────────────────────────
            // HSBC | Banorte | BBVA
            $table->string('banco_participante')->nullable()->after('modalidad_credito')
                  ->comment('HSBC | Banorte | BBVA (aplica a FOVISSSTE Para Todos)');

            // ── Datos del cónyuge (Crédito Conyugal / Mancomunado) ────────────
            $table->string('conyuge_nombre')->nullable()->after('banco_participante');
            $table->string('conyuge_curp')->nullable()->after('conyuge_nombre');
            $table->string('conyuge_rfc')->nullable()->after('conyuge_curp');
            $table->string('conyuge_telefono')->nullable()->after('conyuge_rfc');
            $table->string('conyuge_institucion')->nullable()->after('conyuge_telefono')
                  ->comment('FOVISSSTE | INFONAVIT (institución donde cotiza el cónyuge)');
            $table->string('conyuge_numero_credito')->nullable()->after('conyuge_institucion');

            // ── Datos específicos de Pensionados ──────────────────────────────
            $table->string('numero_pension')->nullable()->after('conyuge_numero_credito');
            $table->string('clave_pension')->nullable()->after('numero_pension')
                  ->comment('101 = Jubilación, 102 = Retiro, 634 = Cesantía');
            $table->date('fecha_inicio_pension')->nullable()->after('clave_pension');
            $table->decimal('monto_pension_mensual', 12, 2)->nullable()->after('fecha_inicio_pension');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn([
                'modalidad_credito',
                'banco_participante',
                'conyuge_nombre',
                'conyuge_curp',
                'conyuge_rfc',
                'conyuge_telefono',
                'conyuge_institucion',
                'conyuge_numero_credito',
                'numero_pension',
                'clave_pension',
                'fecha_inicio_pension',
                'monto_pension_mensual',
            ]);
        });
    }
};
