<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->string('estado_ubicacion')->nullable()->after('estado_prospecto');
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn('estado_ubicacion');
        });
    }
};
