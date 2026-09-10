<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contratos_generados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesor_id')->constrained('users');
            // id generado en la app (uuid) — evita duplicar si un mismo contrato
            // se reintenta subir tras una conexión inestable.
            $table->string('local_id')->unique();

            $table->string('folio')->nullable();
            $table->string('tipo_tramite')->nullable();
            $table->string('ciudad')->nullable();

            $table->string('acreditado_nombre')->nullable();
            $table->string('acreditado_curp')->nullable();
            $table->string('acreditado_rfc')->nullable();
            $table->string('acreditado_nss')->nullable();
            $table->string('acreditado_clave_elector')->nullable();
            $table->text('acreditado_domicilio')->nullable();

            $table->string('solidario_nombre')->nullable();
            $table->string('solidario_curp')->nullable();
            $table->string('solidario_rfc')->nullable();
            $table->text('solidario_domicilio')->nullable();

            $table->decimal('monto_credito', 12, 2)->nullable();
            $table->decimal('honorarios_porcentaje', 5, 2)->nullable();
            $table->decimal('honorarios_monto', 12, 2)->nullable();

            // Rutas en el disco 'local' (privado) — se sirven vía URL firmada.
            $table->string('pdf_path');
            $table->string('ine_acreditado_path')->nullable();
            $table->string('ine_solidario_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos_generados');
    }
};
