<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonio_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('expediente_id')->constrained()->cascadeOnDelete();
            $table->string('email_destino');            // a quién se envió
            $table->string('nombre_destino');           // nombre del acreditado al momento del envío
            $table->timestamp('expires_at');
            $table->timestamp('usado_at')->nullable();  // null = aún no usado
            $table->foreignId('enviado_por')            // usuario admin que lo generó
                  ->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonio_tokens');
    }
};
