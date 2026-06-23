<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // fecha_nacimiento fue eliminada en la migración de precalificación
        // pero se necesita para el perfil del prospecto
        if (! Schema::hasColumn('contactos', 'fecha_nacimiento')) {
            Schema::table('contactos', function (Blueprint $table) {
                $table->date('fecha_nacimiento')->nullable()->after('curp');
            });
        }
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            if (Schema::hasColumn('contactos', 'fecha_nacimiento')) {
                $table->dropColumn('fecha_nacimiento');
            }
        });
    }
};
