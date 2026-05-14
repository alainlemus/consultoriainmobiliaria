<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->string('ruta_archivo')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropColumn('ruta_archivo');
        });
    }
};
