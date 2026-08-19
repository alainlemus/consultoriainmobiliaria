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
        Schema::table('anuncio_fotos', function (Blueprint $table) {
            $table->string('ruta_thumb')->nullable()->after('ruta');
        });

        Schema::table('ubicacion_fotos', function (Blueprint $table) {
            $table->string('ruta_thumb')->nullable()->after('ruta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anuncio_fotos', function (Blueprint $table) {
            $table->dropColumn('ruta_thumb');
        });

        Schema::table('ubicacion_fotos', function (Blueprint $table) {
            $table->dropColumn('ruta_thumb');
        });
    }
};
