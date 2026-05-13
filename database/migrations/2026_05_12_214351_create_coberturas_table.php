<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coberturas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');        // Hidalgo, Veracruz, SLP
            $table->text('descripcion');
            $table->string('detalle');       // dirección o nota de contacto
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coberturas');
    }
};
