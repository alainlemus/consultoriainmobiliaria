<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etapa_tramites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_tramite_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->string('color')->default('gray'); // para el badge visual
            $table->boolean('es_final')->default(false); // etapa de cierre
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapa_tramites');
    }
};
