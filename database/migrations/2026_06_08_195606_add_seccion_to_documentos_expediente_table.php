<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        // 0. Agregar columna seccion si no existe todavía
        if (! Schema::hasColumn('documentos_expediente', 'seccion')) {
            Schema::table('documentos_expediente', function (Blueprint $table) {
                $table->string('seccion', 50)->nullable()->after('tipo');
            });
        }

        if ($isMysql) {
            // 1. Crear índice simple en expediente_id
            $indexes = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'de_expediente_id_idx'");
            if (empty($indexes)) {
                DB::statement('ALTER TABLE documentos_expediente ADD INDEX de_expediente_id_idx (expediente_id)');
            }

            // 2. Quitar unique viejo (expediente_id, tipo)
            $oldUnique = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'documentos_expediente_expediente_id_tipo_unique'");
            if (! empty($oldUnique)) {
                DB::statement('ALTER TABLE documentos_expediente DROP INDEX documentos_expediente_expediente_id_tipo_unique');
            }

            // 3. Extender tipo a 150 chars
            $cols = DB::select("SHOW COLUMNS FROM documentos_expediente LIKE 'tipo'");
            if (! empty($cols)) {
                preg_match('/varchar\((\d+)\)/i', $cols[0]->Type, $m);
                if (! empty($m[1]) && (int) $m[1] < 150) {
                    DB::statement('ALTER TABLE documentos_expediente MODIFY tipo VARCHAR(150) NULL');
                }
            }

            // 4. Agregar nuevo unique (expediente_id, tipo, seccion)
            $newUnique = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'documentos_expediente_tipo_seccion_unique'");
            if (empty($newUnique)) {
                DB::statement('ALTER TABLE documentos_expediente ROW_FORMAT=DYNAMIC');
                DB::statement('ALTER TABLE documentos_expediente ADD UNIQUE KEY documentos_expediente_tipo_seccion_unique (expediente_id, tipo, seccion)');
            }

            // 5. Poblar seccion en registros existentes
            DB::statement("
                UPDATE documentos_expediente de
                JOIN documento_requeridos dr ON dr.nombre = de.tipo
                SET de.seccion = dr.seccion
                WHERE de.seccion IS NULL
            ");
        }
        // SQLite: columna agregada arriba, índice unique se agrega en migración posterior si existe
    }

    public function down(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        if ($isMysql) {
            $newUnique = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'documentos_expediente_tipo_seccion_unique'");
            if (! empty($newUnique)) {
                DB::statement('ALTER TABLE documentos_expediente DROP INDEX documentos_expediente_tipo_seccion_unique');
            }

            $oldUnique = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'documentos_expediente_expediente_id_tipo_unique'");
            if (empty($oldUnique)) {
                DB::statement('ALTER TABLE documentos_expediente ADD UNIQUE KEY documentos_expediente_expediente_id_tipo_unique (expediente_id, tipo)');
            }

            $idx = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'de_expediente_id_idx'");
            if (! empty($idx)) {
                DB::statement('ALTER TABLE documentos_expediente DROP INDEX de_expediente_id_idx');
            }
        }

        Schema::table('documentos_expediente', function (Blueprint $table) {
            if (Schema::hasColumn('documentos_expediente', 'seccion')) {
                $table->dropColumn('seccion');
            }
            $table->string('tipo', 80)->change();
        });
    }
};
