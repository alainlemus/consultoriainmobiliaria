<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->string('tipo');                        // casa, departamento, terreno, local, etc.
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->string('estado');                      // estado de la república
            $table->string('municipio');
            $table->string('colonia')->nullable();
            $table->string('direccion')->nullable();
            $table->unsignedTinyInteger('recamaras')->nullable();
            $table->unsignedTinyInteger('banos')->nullable();
            $table->decimal('metros_construccion', 8, 2)->nullable();
            $table->decimal('metros_terreno', 8, 2)->nullable();
            $table->boolean('acepta_infonavit')->default(false);
            $table->boolean('acepta_fovissste')->default(false);
            $table->json('imagenes')->nullable();          // array de rutas
            $table->string('estatus')->default('disponible'); // disponible, apartada, vendida
            $table->boolean('destacada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propiedades');
    }
};
