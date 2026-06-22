<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            // Permitir NULL para carga desde carpeta cuando no se conoce el tipo de trámite aún
            $table->unsignedBigInteger('tipo_tramite_id')->nullable()->change();
            $table->unsignedBigInteger('etapa_tramite_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_tramite_id')->nullable(false)->change();
            $table->unsignedBigInteger('etapa_tramite_id')->nullable(false)->change();
        });
    }
};
