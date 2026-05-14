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
        Schema::create('foto_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('foto');               // ruta storage
            $table->string('nombre')->nullable(); // nombre del cliente (opcional)
            $table->string('tipo_credito')->nullable(); // ej: INFONAVIT, FOVISSSTE
            $table->string('ciudad')->nullable();
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foto_clientes');
    }
};
