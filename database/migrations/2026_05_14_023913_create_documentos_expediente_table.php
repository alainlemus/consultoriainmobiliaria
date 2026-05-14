<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_expediente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->string('tipo', 80);           // clave interna: 'ine', 'curp', 'predial', etc.
            $table->string('nombre', 150);         // Etiqueta legible: "INE con QR"
            $table->enum('estado', ['pendiente', 'recibido', 'no_aplica'])->default('pendiente');
            $table->string('notas')->nullable();
            $table->timestamps();

            $table->unique(['expediente_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_expediente');
    }
};
