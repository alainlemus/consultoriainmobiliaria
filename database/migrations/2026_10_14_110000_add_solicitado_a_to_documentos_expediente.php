<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A quién se le pidió el documento faltante: 'acreditado' (app del acreditado) o 'asesor'. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->string('solicitado_a', 20)->nullable()->after('solicitud_nota');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropColumn('solicitado_a');
        });
    }
};
