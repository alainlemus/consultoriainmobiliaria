<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * QR por asesor y por medio (lona, tarjeta, panfleto…) para publicidad impresa.
 * Cada escaneo pasa por /a/{codigo} y se registra antes de mandar a la tienda.
 * El acreditado que se registra con el código queda ligado al asesor, y su
 * solicitud de asesoría nace ya asignada a él con origen 'qr'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesor_id')->constrained('users')->cascadeOnDelete();
            // Corto, en mayúsculas y sin letras confusas (0/O, 1/I): se imprime junto al QR.
            $table->string('codigo', 10)->unique();
            $table->string('medio', 30)->default('otro');          // lona, tarjeta, panfleto, anuncio, otro
            $table->string('descripcion', 150)->nullable();        // "Lona plaza principal Huejutla"
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('escaneos_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('codigo_qr_id')->constrained('codigos_qr')->cascadeOnDelete();
            $table->string('plataforma', 10)->default('otro');     // android, ios, otro
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['codigo_qr_id', 'created_at']);
        });

        Schema::table('acreditados', function (Blueprint $table) {
            $table->foreignId('asesor_id')->nullable()->after('contacto_id')->constrained('users')->nullOnDelete();
            $table->foreignId('codigo_qr_id')->nullable()->after('asesor_id')->constrained('codigos_qr')->nullOnDelete();
            // Cómo se supo el asesor: 'codigo_manual' (fase 1); 'referrer' / 'huella' (fase 2)
            $table->string('asesor_origen', 20)->nullable()->after('codigo_qr_id');
        });

        Schema::table('contactos', function (Blueprint $table) {
            $table->foreignId('codigo_qr_id')->nullable()->after('asesor_id')->constrained('codigos_qr')->nullOnDelete();
        });

        // 'qr' en el ENUM de origen (MySQL/MariaDB; SQLite usa TEXT)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE contactos MODIFY COLUMN origen
                ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor','app_acreditado','landing_fovissste','qr')
                DEFAULT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE contactos SET origen = 'app_acreditado' WHERE origen = 'qr'");
            DB::statement("ALTER TABLE contactos MODIFY COLUMN origen
                ENUM('sitio_web','campo','referido','whatsapp','otro','app_movil','admin','asesor','app_acreditado','landing_fovissste')
                DEFAULT NULL");
        }

        Schema::table('contactos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('codigo_qr_id');
        });

        Schema::table('acreditados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('codigo_qr_id');
            $table->dropConstrainedForeignId('asesor_id');
            $table->dropColumn('asesor_origen');
        });

        Schema::dropIfExists('escaneos_qr');
        Schema::dropIfExists('codigos_qr');
    }
};
