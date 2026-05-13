<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gasto_financiados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->string('concepto');            // predial, catastro, avalúo, notaría...
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->string('comprobante_ruta')->nullable();
            $table->string('comprobante_nombre')->nullable();
            $table->enum('estado', ['pendiente_cobro', 'cobrado'])->default('pendiente_cobro');
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gasto_financiados');
    }
};
