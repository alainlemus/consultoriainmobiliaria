<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Préstamo de vehículos, fase 2: prioridad del super admin (desplazar reservas),
 * resguardo en casa, cargas de gasolina con reembolso, alertas y recordatorios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculo_reservas', function (Blueprint $table) {
            $table->foreignId('desplazada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('desplazada_at')->nullable();
            $table->string('desplazada_motivo', 255)->nullable();
            $table->string('estado_previo', 12)->nullable();           // estado antes de que la desplazaran
            $table->json('alertas')->nullable();                       // diferencias detectadas (km, gasolina, retraso…)
            $table->timestamp('recordatorio_salida_at')->nullable();
            $table->timestamp('recordatorio_regreso_at')->nullable();
            $table->timestamp('alerta_retraso_at')->nullable();
        });

        Schema::create('vehiculo_cargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('vehiculo_reservas');
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('movimiento_id')->constrained('vehiculo_movimientos');   // guarda las fotos (ticket, tablero)
            $table->unsignedInteger('km');
            $table->decimal('litros', 6, 2);
            $table->decimal('monto', 10, 2);
            $table->string('pago', 20);                                 // tarjeta_empresa | efectivo_empresa | reembolso
            $table->timestamp('reembolsado_at')->nullable();
            $table->foreignId('reembolsado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculo_cargas');
        Schema::table('vehiculo_reservas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desplazada_por_id');
            $table->dropColumn(['desplazada_at', 'desplazada_motivo', 'estado_previo', 'alertas', 'recordatorio_salida_at', 'recordatorio_regreso_at', 'alerta_retraso_at']);
        });
    }
};
