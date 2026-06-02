<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_pasos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();   // identificador único: bienvenida, servicio, nombre...
            $table->string('tipo', 30);              // mensaje, seleccion, texto_libre, condicional
            $table->string('etiqueta', 100);         // nombre visible en el panel
            $table->text('mensaje');                 // texto que se envía al usuario
            $table->json('opciones')->nullable();    // para tipo=seleccion: [{valor, etiqueta, requiere_curp}]
            $table->string('siguiente_paso', 50)->nullable(); // clave del paso siguiente (null = crear prospecto)
            $table->boolean('activo')->default(true);
            $table->boolean('requerido')->default(true); // si false, el usuario puede escribir 'omitir'
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_pasos');
    }
};
