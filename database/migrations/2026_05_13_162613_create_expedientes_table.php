<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();             // EXP-2026-0001

            // Relaciones
            $table->foreignId('tipo_tramite_id')->constrained();
            $table->foreignId('etapa_tramite_id')->constrained();
            $table->foreignId('asesor_id')->constrained('users'); // asesor asignado
            $table->foreignId('contacto_id')->nullable()->constrained('contactos'); // prospecto origen
            $table->foreignId('revisor_id')->nullable()->constrained('users');      // dueño/admin

            // Estado general
            $table->enum('estado', [
                'en_proceso', 'pausado', 'aprobado', 'firmado', 'cerrado', 'cancelado'
            ])->default('en_proceso');

            // --- SECCIÓN A: ACREDITADO ---
            $table->string('acreditado_nombre');
            $table->string('acreditado_curp', 18)->nullable();
            $table->string('acreditado_rfc', 13)->nullable();
            $table->date('acreditado_fecha_nacimiento')->nullable();
            $table->string('acreditado_telefono')->nullable();
            $table->string('acreditado_email')->nullable();
            $table->string('acreditado_domicilio')->nullable();
            $table->string('acreditado_colonia')->nullable();
            $table->string('acreditado_municipio')->nullable();
            $table->string('acreditado_estado')->nullable();
            $table->string('acreditado_cp', 10)->nullable();
            $table->enum('acreditado_estado_civil', [
                'soltero', 'casado', 'union_libre', 'divorciado', 'viudo'
            ])->nullable();
            $table->integer('acreditado_antiguedad_laboral')->nullable(); // años
            $table->string('acreditado_numero_credito')->nullable();
            // Personas autorizadas (JSON array)
            $table->json('acreditado_personas_autorizadas')->nullable();
            // Referencias personales (JSON array)
            $table->json('acreditado_referencias')->nullable();

            // --- SECCIÓN B: VENDEDOR ---
            $table->string('vendedor_nombre')->nullable();
            $table->string('vendedor_curp', 18)->nullable();
            $table->string('vendedor_rfc', 13)->nullable();
            $table->string('vendedor_telefono')->nullable();
            $table->string('vendedor_email')->nullable();
            $table->string('vendedor_domicilio')->nullable();
            $table->boolean('vendedor_requiere_acta_matrimonio')->default(false);
            $table->string('vendedor_banco')->nullable();
            $table->string('vendedor_clabe', 18)->nullable();

            // --- SECCIÓN C: VIVIENDA ---
            $table->string('vivienda_calle')->nullable();
            $table->string('vivienda_numero')->nullable();
            $table->string('vivienda_colonia')->nullable();
            $table->string('vivienda_municipio')->nullable();
            $table->string('vivienda_estado')->nullable();
            $table->string('vivienda_cp', 10)->nullable();
            $table->enum('vivienda_tipo', ['casa', 'departamento', 'terreno'])->nullable();
            $table->text('vivienda_descripcion_titulo')->nullable(); // datos del título de propiedad

            // --- DATOS DEL TRÁMITE ---
            $table->enum('uso_credito', [
                'retiro_directo', 'compraventa', 'construccion', 'otro'
            ])->default('retiro_directo');
            $table->decimal('monto_credito', 12, 2)->nullable();
            $table->decimal('subcuenta_vivienda', 12, 2)->nullable();
            $table->decimal('monto_total_estimado', 12, 2)->nullable();
            $table->decimal('honorarios_porcentaje', 5, 2)->nullable();
            $table->decimal('honorarios_monto', 12, 2)->nullable();

            // Estado de pago
            $table->boolean('honorarios_pagados')->default(false);
            $table->date('fecha_pago_honorarios')->nullable();
            $table->decimal('total_gastos_financiados', 12, 2)->default(0);

            $table->text('notas_internas')->nullable();
            $table->date('fecha_apertura')->nullable();
            $table->date('fecha_cierre')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};
