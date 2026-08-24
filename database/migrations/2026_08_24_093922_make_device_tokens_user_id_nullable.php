<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * device_tokens.user_id era NOT NULL con FK a users — impedía registrar
 * dispositivos de Acreditado (que no está en la tabla users). La columna
 * polimórfica tokenable_type/tokenable_id ya existía (migración anterior)
 * pero nunca se pudo usar sola porque user_id seguía siendo obligatoria.
 *
 * MySQL: ALTER TABLE crudo (ya corrido y verificado en la BD de desarrollo —
 * no se vuelve a ejecutar ahí, Laravel no re-corre migraciones ya aplicadas).
 * SQLite (tests, BD fresca en cada RefreshDatabase): SÍ enforcea NOT NULL
 * sobre columnas ya creadas — a diferencia de lo que decía un comentario
 * anterior aquí, verificado con un test real que fallaba con
 * "NOT NULL constraint failed: device_tokens.user_id". Se usa ->change()
 * (requiere doctrine/dbal, ya instalado) solo para SQLite; para MySQL se
 * deja el ALTER crudo ya probado, para no tocar el camino de producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('device_tokens', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            DB::statement('ALTER TABLE device_tokens MODIFY user_id BIGINT UNSIGNED NULL');

            Schema::table('device_tokens', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
            return;
        }

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('device_tokens', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });

            // Antes de volver a NOT NULL hay que asegurar que no queden filas con
            // user_id nulo (tokens de Acreditado) — se eliminan, son recreables.
            DB::table('device_tokens')->whereNull('user_id')->delete();

            DB::statement('ALTER TABLE device_tokens MODIFY user_id BIGINT UNSIGNED NOT NULL');

            Schema::table('device_tokens', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
            return;
        }

        DB::table('device_tokens')->whereNull('user_id')->delete();
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
