<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las escuelas pueden registrarse sin GPS (el asesor no está físicamente ahí).
     * Se pueden geolocalizar después desde el mapa.
     */
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->decimal('latitud',  10, 7)->nullable()->change();
            $table->decimal('longitud', 10, 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->decimal('latitud',  10, 7)->nullable(false)->change();
            $table->decimal('longitud', 10, 7)->nullable(false)->change();
        });
    }
};
