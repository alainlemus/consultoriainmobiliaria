<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tipo y seccion ya existen (el primer intento parcial los creó)
        // Solo completamos lo que faltó: reemplazar el unique y poblar seccion

        // 1. Crear índice simple en expediente_id para que el FK tenga soporte
        //    cuando quitemos el unique que lo respaldaba
        $indexes = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'de_expediente_id_idx'");
        if (empty($indexes)) {
            DB::statement('ALTER TABLE documentos_expediente ADD INDEX de_expediente_id_idx (expediente_id)');
        }

        // 2. Quitar unique viejo (expediente_id, tipo)
        $oldUnique = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'documentos_expediente_expediente_id_tipo_unique'");
        if (! empty($oldUnique)) {
            DB::statement('ALTER TABLE documentos_expediente DROP INDEX documentos_expediente_expediente_id_tipo_unique');
        }

        // 3. Extender tipo a 150 chars si todavía es más corto (necesario antes de crear el índice)
        $cols = DB::select("SHOW COLUMNS FROM documentos_expediente LIKE 'tipo'");
        if (! empty($cols)) {
            preg_match('/varchar\((\d+)\)/i', $cols[0]->Type, $m);
            if (! empty($m[1]) && (int) $m[1] < 150) {
                DB::statement('ALTER TABLE documentos_expediente MODIFY tipo VARCHAR(150) NULL');
            }
        }

        // 4. Agregar nuevo unique (expediente_id, tipo, seccion) — sin prefijo; requiere InnoDB DYNAMIC row format (MySQL 5.7.7+)
        $newUnique = DB::select("SHOW INDEX FROM documentos_expediente WHERE Key_name = 'documentos_expediente_tipo_seccion_unique'");
        if (empty($newUnique)) {
            DB::statement('ALTER TABLE documentos_expediente ROW_FORMAT=DYNAMIC');
            DB::statement('ALTER TABLE documentos_expediente ADD UNIQUE KEY documentos_expediente_tipo_seccion_unique (expediente_id, tipo, seccion)');
        }

        // 5. Poblar seccion en registros existentes a partir de documento_requeridos
        DB::statement("
            UPDATE documentos_expediente de
            JOIN documento_requeridos dr ON dr.nombre = de.tipo
            SET de.seccion = dr.seccion
            WHERE de.seccion IS NULL
        ");
    }

    public function down(): void
    {
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

        Schema::table('documentos_expediente', function (Blueprint $table) {
            if (Schema::hasColumn('documentos_expediente', 'seccion')) {
                $table->dropColumn('seccion');
            }
            $table->string('tipo', 80)->change();
        });
    }
};
