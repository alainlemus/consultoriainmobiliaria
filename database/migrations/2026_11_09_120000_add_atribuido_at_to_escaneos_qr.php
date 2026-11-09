<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 del QR: un escaneo se "consume" cuando una instalación nueva lo
 * reclama (Install Referrer en Android o cruce por huella IP + sistema).
 * Así un mismo escaneo no se atribuye a dos celulares.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escaneos_qr', function (Blueprint $table) {
            $table->timestamp('atribuido_at')->nullable()->after('user_agent');
            $table->string('atribucion_tipo', 20)->nullable()->after('atribuido_at'); // referrer | huella
        });
    }

    public function down(): void
    {
        Schema::table('escaneos_qr', function (Blueprint $table) {
            $table->dropColumn(['atribuido_at', 'atribucion_tipo']);
        });
    }
};
