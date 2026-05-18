<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->string('municipio', 100)->nullable()->after('notas');
            $table->string('estado', 100)->nullable()->after('municipio');
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn(['municipio', 'estado']);
        });
    }
};
