<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asesor_id')->constrained('users');
            $table->decimal('monto_base', 12, 2);          // honorarios cobrados al cliente
            $table->decimal('porcentaje_comision', 5, 2);  // % al asesor
            $table->decimal('monto_comision', 12, 2);      // calculado
            $table->enum('estado', ['pendiente', 'aprobada', 'pagada'])->default('pendiente');
            $table->date('fecha_generacion');
            $table->date('fecha_aprobacion')->nullable();
            $table->date('fecha_pago')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisions');
    }
};
