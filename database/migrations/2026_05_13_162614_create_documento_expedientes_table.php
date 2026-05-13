<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_expedientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('documento_requerido_id')->nullable()->constrained();
            $table->string('nombre');
            $table->enum('seccion', ['acreditado', 'vendedor', 'vivienda', 'tramite']);
            $table->enum('estado', ['pendiente', 'entregado', 'con_observaciones', 'aprobado'])
                  ->default('pendiente');
            $table->string('ruta_archivo')->nullable();   // storage path
            $table->string('nombre_archivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users');
            $table->timestamp('fecha_entrega')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_expedientes');
    }
};
