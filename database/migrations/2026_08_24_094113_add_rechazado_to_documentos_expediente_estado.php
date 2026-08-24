<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega 'rechazado' al enum de estado: hoy un documento subido que el
 * asesor detecta ilegible/incorrecto solo puede quedar en "recibido"
 * (incorrecto) o "pendiente" (indistinguible de "nunca se subió"), sin
 * forma de decirle al acreditado que debe volver a subirlo y por qué.
 *
 * MySQL: ALTER TABLE crudo (ya corrido y verificado en la BD de desarrollo).
 * SQLite (tests, BD fresca en cada RefreshDatabase): Laravel SÍ crea un
 * CHECK constraint para columnas enum() ahí — un comentario anterior decía
 * lo contrario, pero un test real lo tumbó con "CHECK constraint failed:
 * estado". Se usa ->change() (doctrine/dbal) solo para SQLite; MySQL se
 * deja con el ALTER crudo ya probado, para no tocar el camino de producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documentos_expediente MODIFY estado ENUM('pendiente', 'recibido', 'rechazado', 'no_aplica') NOT NULL DEFAULT 'pendiente'");
            return;
        }

        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'recibido', 'rechazado', 'no_aplica'])->default('pendiente')->change();
        });
    }

    public function down(): void
    {
        DB::table('documentos_expediente')->where('estado', 'rechazado')->update(['estado' => 'pendiente']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE documentos_expediente MODIFY estado ENUM('pendiente', 'recibido', 'no_aplica') NOT NULL DEFAULT 'pendiente'");
            return;
        }

        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->enum('estado', ['pendiente', 'recibido', 'no_aplica'])->default('pendiente')->change();
        });
    }
};
