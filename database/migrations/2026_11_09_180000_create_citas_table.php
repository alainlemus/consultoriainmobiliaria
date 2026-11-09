<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Citas de asesoría: en oficina (zonas de cobertura con citas activas) o a
 * domicilio. Las atiende el super admin; él las confirma, reagenda o cancela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coberturas', function (Blueprint $table) {
            $table->boolean('citas_activas')->default(false)->after('activo');
            $table->json('horario_citas')->nullable()->after('citas_activas');
        });

        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contacto_id')->nullable()->constrained('contactos')->nullOnDelete();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->nullOnDelete();
            $table->foreignId('acreditado_id')->nullable()->constrained('acreditados')->nullOnDelete();
            $table->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atiende_id')->nullable()->constrained('users')->nullOnDelete();   // super admin que atiende
            $table->foreignId('cobertura_id')->nullable()->constrained('coberturas')->nullOnDelete();
            $table->string('tipo', 12)->default('oficina');                 // oficina | domicilio
            $table->string('direccion', 255)->nullable();                     // domicilio del acreditado (tipo domicilio)
            $table->string('nombre', 120);                                    // a quién se atiende
            $table->string('telefono', 20)->nullable();
            $table->timestamp('inicia_at');
            $table->timestamp('termina_at');
            $table->string('estado', 14)->default('solicitada');             // solicitada | confirmada | cancelada | realizada | no_asistio
            $table->string('creada_por_tipo', 12);                            // asesor | acreditado | admin
            $table->unsignedBigInteger('creada_por_id')->nullable();
            $table->text('notas')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->timestamp('confirmada_at')->nullable();
            $table->timestamp('reagendada_at')->nullable();
            $table->timestamp('recordatorio_dia_at')->nullable();
            $table->timestamp('recordatorio_horas_at')->nullable();
            $table->timestamps();
            $table->index(['inicia_at', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
        Schema::table('coberturas', function (Blueprint $table) {
            $table->dropColumn(['citas_activas', 'horario_citas']);
        });
    }
};
