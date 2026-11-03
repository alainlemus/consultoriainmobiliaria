<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encargos a gestores: la administración (super_admin) envía un expediente a
 * un gestor para que haga un trámite en una dependencia pública.
 *
 * Flujo (estado del encargo):
 *   por_revisar        → el gestor revisa cada documento compartido (✓ / ✗ + nota)
 *   con_observaciones  → marcó alguno mal; el admin lo reemplaza y lo reenvía (vuelta + 1)
 *   en_tramite         → todo correcto; el gestor ya puede subir lo que obtiene
 *   terminado          → el admin validó todo lo que subió el gestor
 *   cancelado
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encargos_gestor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('gestor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instrucciones');
            $table->date('fecha_entrega')->nullable();
            // Secciones de datos del expediente que verá el gestor: acreditado, vendedor, inmueble, credito
            $table->json('datos_compartidos')->nullable();
            $table->string('estado', 30)->default('por_revisar');
            $table->unsignedSmallInteger('vuelta')->default(1);
            $table->timestamp('tramite_iniciado_at')->nullable();
            $table->timestamp('terminado_at')->nullable();
            $table->timestamps();

            $table->index(['gestor_id', 'estado']);
        });

        // Documentos del expediente que el admin compartió con el gestor y la revisión de cada uno
        Schema::create('encargo_gestor_documento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encargo_gestor_id')->constrained('encargos_gestor')->cascadeOnDelete();
            $table->foreignId('documento_expediente_id')->constrained('documentos_expediente')->cascadeOnDelete();
            $table->string('revision', 20)->default('pendiente'); // pendiente | correcto | incorrecto
            $table->text('nota')->nullable();
            $table->unsignedSmallInteger('vuelta')->default(1);    // vuelta en la que se revisó por última vez
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->unique(['encargo_gestor_id', 'documento_expediente_id'], 'encargo_doc_unico');
        });

        // Historial del ida y vuelta
        Schema::create('encargo_gestor_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encargo_gestor_id')->constrained('encargos_gestor')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 40);
            $table->text('detalle')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Lo que sube el gestor se guarda como documento del expediente, ligado a su encargo
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->foreignId('encargo_gestor_id')->nullable()->after('expediente_id')->constrained('encargos_gestor')->nullOnDelete();
            $table->foreignId('subido_por_id')->nullable()->after('encargo_gestor_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documentos_expediente', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subido_por_id');
            $table->dropConstrainedForeignId('encargo_gestor_id');
        });
        Schema::dropIfExists('encargo_gestor_eventos');
        Schema::dropIfExists('encargo_gestor_documento');
        Schema::dropIfExists('encargos_gestor');
    }
};
