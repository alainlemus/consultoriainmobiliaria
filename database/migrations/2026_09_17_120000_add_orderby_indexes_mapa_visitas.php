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
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->index('visitado_en');
        });

        Schema::table('anuncios', function (Blueprint $table) {
            $table->index('colocado_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropIndex(['visitado_en']);
        });

        Schema::table('anuncios', function (Blueprint $table) {
            $table->dropIndex(['colocado_en']);
        });
    }
};
