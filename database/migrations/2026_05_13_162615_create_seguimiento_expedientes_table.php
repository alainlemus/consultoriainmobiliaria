<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_expedientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users');
            $table->enum('tipo', ['nota', 'cambio_etapa', 'documento', 'llamada', 'visita', 'sistema'])
                  ->default('nota');
            $table->text('descripcion');
            $table->foreignId('etapa_anterior_id')->nullable()->constrained('etapa_tramites');
            $table->foreignId('etapa_nueva_id')->nullable()->constrained('etapa_tramites');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_expedientes');
    }
};
