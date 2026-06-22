<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del proceso FOVISSSTE completo (pasos 2–20 del cliente).
 * También corrige el bug de vivienda_superficie que existía en el form
 * de Filament pero no tenía columna en la BD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {

            // ── Bug fix: vivienda_superficie ──────────────────────────
            // El form de Filament ya tenía este campo pero no existía en BD.
            $table->decimal('vivienda_superficie', 10, 2)->nullable()->after('vivienda_tipo');

            // ── Paso 4-J: Portal FOVISSSTE ────────────────────────────
            // Inscripción al portal de activación continua (CURP+correo+cel)
            $table->boolean('portal_fovissste_activado')->default(false)->after('acreditado_referencias');
            $table->text('portal_fovissste_notas')->nullable()->after('portal_fovissste_activado');

            // ── Paso 10: Catastro / subdivisión ───────────────────────
            // Regla 3:1 FOVISSSTE: si el predio es mayor a la fracción a vender
            $table->boolean('requiere_subdivision')->default(false)->after('portal_fovissste_notas');
            $table->decimal('superficie_total_predio', 10, 2)->nullable()->after('requiere_subdivision');

            // ── Pasos 14-15: CUV (Clave Única de Vivienda - RUV) ──────
            $table->string('cuv', 50)->nullable()->after('superficie_total_predio');
            $table->date('cuv_fecha_pago')->nullable()->after('cuv');
            $table->boolean('cuv_activa')->default(false)->after('cuv_fecha_pago');

            // ── Paso 16: Exención ISR del vendedor ───────────────────
            // El vendedor no ha vendido en los últimos 3 años → exento de ISR
            $table->boolean('vendedor_exencion_isr')->default(false)->after('vendedor_clabe');
            // Si no aplica exención, requiere avalúo referido (tiene costo)
            $table->boolean('vendedor_requiere_avaluo_referido')->default(false)->after('vendedor_exencion_isr');

            // ── Paso 18: Instrucción notarial de SOFOM ────────────────
            $table->boolean('instruccion_notarial_recibida')->default(false)->after('notas_internas');
            $table->date('instruccion_notarial_fecha')->nullable()->after('instruccion_notarial_recibida');

            // ── Paso 19: CLG y notaría ────────────────────────────────
            // CLG = Certificado de Libertad de Gravamen (30 días hábiles)
            $table->boolean('clg_solicitado')->default(false)->after('instruccion_notarial_fecha');
            $table->date('clg_fecha_solicitud')->nullable()->after('clg_solicitado');
            $table->boolean('clg_recibido')->default(false)->after('clg_fecha_solicitud');
            // fecha_limite_firma: calculada = clg_fecha_solicitud + 30 días hábiles
            $table->date('fecha_limite_firma')->nullable()->after('clg_recibido');
            $table->date('fecha_firma')->nullable()->after('fecha_limite_firma');

            // ── Paso 20: Guarda Valores y pago ────────────────────────
            // Expediente firmado → Guarda Valores FOVISSSTE → pago en 20 días hábiles
            $table->date('fecha_envio_guarda_valores')->nullable()->after('fecha_firma');
            // fecha_esperada_pago: calculada = fecha_envio_guarda_valores + 20 días hábiles
            $table->date('fecha_esperada_pago')->nullable()->after('fecha_envio_guarda_valores');
            $table->boolean('pago_recibido')->default(false)->after('fecha_esperada_pago');
            $table->date('fecha_pago_recibido')->nullable()->after('pago_recibido');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropColumn([
                'vivienda_superficie',
                'portal_fovissste_activado',
                'portal_fovissste_notas',
                'requiere_subdivision',
                'superficie_total_predio',
                'cuv',
                'cuv_fecha_pago',
                'cuv_activa',
                'vendedor_exencion_isr',
                'vendedor_requiere_avaluo_referido',
                'instruccion_notarial_recibida',
                'instruccion_notarial_fecha',
                'clg_solicitado',
                'clg_fecha_solicitud',
                'clg_recibido',
                'fecha_limite_firma',
                'fecha_firma',
                'fecha_envio_guarda_valores',
                'fecha_esperada_pago',
                'pago_recibido',
                'fecha_pago_recibido',
            ]);
        });
    }
};
