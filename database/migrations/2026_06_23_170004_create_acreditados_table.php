<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditados', function (Blueprint $table) {
            $table->id();

            // Credenciales
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->rememberToken();

            // Datos personales (usados para vincular con el expediente)
            $table->string('curp', 18)->unique()->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('nss', 15)->nullable();
            $table->string('rfc', 13)->nullable();
            $table->string('foto_perfil')->nullable();

            // Vinculación con el CRM
            $table->foreignId('contacto_id')->nullable()->constrained('contactos')->nullOnDelete();

            // Estado de la cuenta
            $table->boolean('activo')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('curp_verified_at')->nullable(); // cuando se vinculó al expediente

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditados');
    }
};
