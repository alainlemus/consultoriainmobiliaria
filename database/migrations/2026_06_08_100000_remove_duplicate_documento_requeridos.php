<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Elimina los 37 registros duplicados en documento_requeridos
 * originados por una segunda ejecución del seeder.
 * Los duplicados tienen ID >= 260 y solo existen en los trámites 1, 2, 8 y 9.
 * Ningún documento real subido (documentos_expediente) referencia estos nombres
 * de forma exclusiva, por lo que el borrado es seguro.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('documento_requeridos')
            ->whereIn('tipo_tramite_id', [1, 2, 8, 9])
            ->where('id', '>=', 260)
            ->delete();
    }

    public function down(): void
    {
        // No se restauran — volver a correr el seeder correspondiente si se necesita revertir.
    }
};
