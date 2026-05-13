<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_requeridos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_tramite_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->enum('seccion', ['acreditado', 'vendedor', 'vivienda']);
            $table->text('descripcion')->nullable();
            $table->boolean('obligatorio')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_requeridos');
    }
};
