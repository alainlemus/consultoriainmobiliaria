<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de anuncios publicitarios registrados por los asesores.
 *
 * Propósito: rastrear dónde se han colocado lonas, hojas, propaganda, etc.
 * para que otros asesores sepan qué territorios ya están cubiertos y no dupliquen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();

            // Asesor que colocó el anuncio
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Coordenadas GPS (obligatorias — el asesor está físicamente en el lugar)
            $table->decimal('latitud',  10, 7);
            $table->decimal('longitud', 10, 7);

            // Tipo de anuncio
            $table->enum('tipo', [
                'lona',
                'hoja_tienda',
                'hoja_poste',
                'volante',
                'otro',
            ])->default('hoja_poste');

            // Estado del anuncio
            $table->enum('estado', ['activo', 'retirado'])->default('activo');

            // Datos descriptivos
            $table->string('descripcion', 300)->nullable();   // notas del asesor
            $table->string('direccion',   500)->nullable();   // dirección aproximada
            $table->string('colonia',     150)->nullable();
            $table->string('municipio',   100)->nullable();
            $table->string('estado_geo',  100)->nullable();   // estado de México (evita colisión con columna 'estado')

            // Fecha real de colocación (puede ser diferente a created_at si se registra después)
            $table->date('colocado_en')->useCurrent();

            $table->timestamps();

            // Índice geoespacial aproximado para búsquedas por zona
            $table->index(['latitud', 'longitud']);
            $table->index(['municipio', 'estado_geo']);
            $table->index('user_id');
            $table->index('estado');
        });

        Schema::create('anuncio_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anuncio_id')
                  ->constrained('anuncios')
                  ->cascadeOnDelete();
            $table->string('ruta');
            $table->string('mime', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncio_fotos');
        Schema::dropIfExists('anuncios');
    }
};
