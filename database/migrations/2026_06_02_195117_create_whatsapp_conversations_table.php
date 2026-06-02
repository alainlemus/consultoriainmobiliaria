<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id')->unique(); // ej: "5217751557436@c.us"
            $table->string('telefono', 20);      // ej: "7751557436"
            $table->string('paso', 50)->default('inicio');
            // pasos: inicio, esperando_servicio, esperando_nombre,
            //        esperando_correo, esperando_curp, completado
            $table->json('datos')->nullable();   // datos capturados del prospecto
            $table->timestamp('ultimo_mensaje_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
