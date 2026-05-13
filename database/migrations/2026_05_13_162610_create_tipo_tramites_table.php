<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_tramites', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                          // FOVISSSTE, INFONAVIT, etc.
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->decimal('porcentaje_honorarios', 5, 2)->default(0); // % sobre crédito
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_tramites');
    }
};
