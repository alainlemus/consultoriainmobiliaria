<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('nombre_lugar')->nullable()->after('tipo');
            $table->text('direccion')->nullable()->after('nombre_lugar');
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn(['nombre_lugar', 'direccion']);
        });
    }
};
