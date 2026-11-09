<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seguimiento fijo del trámite (7 pasos iguales para todos, avanzan solos con
 * los eventos del sistema). La columna `cuv` ya existía (la llena el OCR);
 * aquí solo se agrega quién y cuándo la registró.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            if (! Schema::hasColumn('expedientes', 'seguimiento')) {
                $table->string('seguimiento', 40)->default('validacion_documentos')->after('estado');
            }
            if (! Schema::hasColumn('expedientes', 'seguimiento_at')) {
                $table->timestamp('seguimiento_at')->nullable()->after('seguimiento');
            }
            if (! Schema::hasColumn('expedientes', 'cuv')) {
                $table->string('cuv', 50)->nullable();
            }
            if (! Schema::hasColumn('expedientes', 'cuv_registrado_at')) {
                $table->timestamp('cuv_registrado_at')->nullable()->after('cuv');
            }
            if (! Schema::hasColumn('expedientes', 'cuv_registrado_por')) {
                $table->foreignId('cuv_registrado_por')->nullable()->after('cuv_registrado_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cuv_registrado_por');
            $table->dropColumn(['seguimiento', 'seguimiento_at', 'cuv_registrado_at']);
        });
    }
};
