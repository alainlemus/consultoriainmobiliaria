<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            // Semáforo solo aplica cuando tipo = 'escuela'.
            // amarillo = estado inicial (visitada, sin clientes aún)
            // verde    = hay maestros que usaron el servicio
            // rojo     = acceso denegado / no les interesa
            $table->enum('semaforo', ['verde', 'amarillo', 'rojo'])
                  ->default('amarillo')
                  ->after('tipo');

            $table->text('semaforo_notas')->nullable()->after('semaforo');
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropColumn(['semaforo', 'semaforo_notas']);
        });
    }
};
