<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('id_local')->index();   // UUID generado en la app
            $table->string('tipo');                // p.ej. crear_contacto
            $table->json('datos');
            $table->enum('estado', ['pendiente', 'procesando', 'ok', 'error'])->default('pendiente');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('procesado_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
