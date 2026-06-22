<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            // ── Eliminar campos viejos de precalificación ────────────────
            $table->dropColumn([
                'fecha_nacimiento',
                'antiguedad_laboral',
                'salario_mensual',
                'tipo_credito_interes',
                'monto_credito_estimado',
                'subcuenta_vivienda',
                'notas_precalificacion',
                'resultado_precalificacion',
            ]);

            // ── Agregar campos nuevos FOVISSSTE ──────────────────────────
            $table->string('estado_uso_credito',    100)->nullable()->after('curp');
            $table->string('municipio_uso_credito', 100)->nullable()->after('estado_uso_credito');
            $table->string('estado_residencia',     100)->nullable()->after('municipio_uso_credito');
            $table->string('regimen_pensionario',    80)->nullable()->after('estado_residencia');
            $table->boolean('tiene_discapacidad')->default(false)->after('regimen_pensionario');
            $table->string('simulador_screenshot')->nullable()->after('tiene_discapacidad');
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn([
                'estado_uso_credito',
                'municipio_uso_credito',
                'estado_residencia',
                'regimen_pensionario',
                'tiene_discapacidad',
                'simulador_screenshot',
            ]);

            // Restaurar campos viejos
            $table->date('fecha_nacimiento')->nullable();
            $table->decimal('antiguedad_laboral', 5, 2)->nullable();
            $table->decimal('salario_mensual', 12, 2)->nullable();
            $table->string('tipo_credito_interes', 80)->nullable();
            $table->decimal('monto_credito_estimado', 12, 2)->nullable();
            $table->decimal('subcuenta_vivienda', 12, 2)->nullable();
            $table->text('notas_precalificacion')->nullable();
            $table->string('resultado_precalificacion', 30)->nullable();
        });
    }
};
