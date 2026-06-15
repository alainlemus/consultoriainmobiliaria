<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            // Escuela a la que pertenece el prospecto (maestro).
            // Nullable: la mayoría de contactos no son maestros.
            // SET NULL al borrar la escuela para no perder al contacto.
            $table->foreignId('escuela_id')
                  ->nullable()
                  ->after('asesor_id')
                  ->constrained('ubicaciones')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropForeign(['escuela_id']);
            $table->dropColumn('escuela_id');
        });
    }
};
