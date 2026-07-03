<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acreditado_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acreditado_id')->constrained('acreditados')->cascadeOnDelete();
            $table->foreignId('contacto_id')->nullable()->constrained('contactos')->nullOnDelete();
            $table->foreignId('tipo_tramite_id')->nullable()->constrained('tipo_tramites')->nullOnDelete();
            $table->string('servicio')->nullable();          // nombre del servicio en el momento
            $table->text('mensaje')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('estado_solicitud')->default('pendiente'); // pendiente | atendida | cancelada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acreditado_solicitudes');
    }
};
