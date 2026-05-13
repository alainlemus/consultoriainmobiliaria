<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            $table->enum('origen', ['sitio_web', 'campo', 'referido', 'whatsapp', 'otro'])
                  ->default('sitio_web')->after('asesor_id');
            $table->string('curp', 18)->nullable()->after('origen');
            $table->date('fecha_nacimiento')->nullable()->after('curp');
            $table->integer('antiguedad_laboral')->nullable()->after('fecha_nacimiento'); // años
            $table->decimal('salario_mensual', 10, 2)->nullable()->after('antiguedad_laboral');
            $table->enum('tipo_credito_interes', ['fovissste', 'infonavit', 'ambos', 'otro'])
                  ->nullable()->after('salario_mensual');
            // Resultado precalificación
            $table->decimal('monto_credito_estimado', 12, 2)->nullable()->after('tipo_credito_interes');
            $table->decimal('subcuenta_vivienda', 12, 2)->nullable()->after('monto_credito_estimado');
            $table->text('notas_precalificacion')->nullable()->after('subcuenta_vivienda');
            // Estado del ciclo de vida del prospecto
            $table->enum('estado_prospecto', [
                'nuevo', 'contactado', 'precalificado', 'propuesta_enviada', 'convertido', 'descartado'
            ])->default('nuevo')->after('notas_precalificacion');
            $table->date('fecha_primer_contacto')->nullable()->after('estado_prospecto');
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropForeign(['asesor_id']);
            $table->dropColumn([
                'asesor_id', 'origen', 'curp', 'fecha_nacimiento', 'antiguedad_laboral',
                'salario_mensual', 'tipo_credito_interes', 'monto_credito_estimado',
                'subcuenta_vivienda', 'notas_precalificacion', 'estado_prospecto',
                'fecha_primer_contacto',
            ]);
        });
    }
};
