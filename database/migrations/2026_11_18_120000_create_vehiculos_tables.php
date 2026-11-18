<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Préstamo de vehículos a asesores (motos hoy, coches después).
 * Tipos configurables (fotos y checklist), vehículos, reservas con fila por
 * orden de llegada, movimientos (salida, entrega, llaves) con sus fotos,
 * y licencia de manejo del asesor (solo aviso, no bloquea).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehiculo_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);                         // Moto | Coche | ...
            $table->string('icono', 60)->nullable();              // heroicon / emoji para la app
            $table->json('fotos_requeridas')->nullable();         // [{clave, etiqueta}] además del tablero
            $table->json('checklist')->nullable();                // ["Casco", ...]
            $table->string('licencia_tipo', 60)->nullable();      // texto informativo
            $table->unsignedInteger('servicio_cada_km')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_tipo_id')->constrained('vehiculo_tipos');
            $table->string('nombre', 60);
            $table->string('placa', 20)->nullable();
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 60)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('color', 30)->nullable();
            $table->string('foto')->nullable();
            $table->unsignedSmallInteger('capacidad_tanque_l')->nullable();
            $table->unsignedInteger('km_actual')->default(0);
            $table->unsignedTinyInteger('gasolina_actual')->nullable();   // 0 reserva … 4 lleno
            $table->unsignedInteger('km_proximo_servicio')->nullable();
            $table->date('vence_seguro')->nullable();
            $table->date('vence_tarjeta')->nullable();
            $table->string('estado', 12)->default('activo');              // activo | taller | baja
            $table->string('llaves_en', 10)->default('oficina');          // oficina | usuario
            $table->foreignId('llaves_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('llaves_codigo', 4)->nullable();
            $table->foreignId('llaves_codigo_user_id')->nullable()->constrained('users')->nullOnDelete();   // quién generó el código (entrega las llaves)
            $table->timestamp('llaves_codigo_expira_at')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('vehiculo_reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('asesor_id')->constrained('users');
            $table->boolean('todo_el_dia')->default(false);
            $table->timestamp('inicio');
            $table->timestamp('fin');
            $table->boolean('resguardo_casa')->default(false);
            $table->string('motivo', 255);
            $table->string('destino', 255)->nullable();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->foreignId('contacto_id')->nullable()->constrained('contactos')->nullOnDelete();
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->nullOnDelete();
            $table->string('estado', 12)->default('solicitada');   // solicitada | en_espera | autorizada | en_uso | entregada | rechazada | cancelada
            $table->foreignId('decidido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decidido_at')->nullable();
            $table->string('motivo_decision', 255)->nullable();
            $table->timestamps();
            $table->index(['vehiculo_id', 'estado', 'inicio']);
        });

        Schema::create('vehiculo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos');
            $table->foreignId('reserva_id')->nullable()->constrained('vehiculo_reservas')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo', 10);                       // salida | entrega | llaves
            $table->unsignedInteger('km')->nullable();
            $table->unsignedTinyInteger('gasolina')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('notas_danos')->nullable();
            $table->json('checklist')->nullable();
            $table->string('llaves_modo', 14)->nullable();    // oficina | codigo | ya_las_tenia
            $table->foreignId('llaves_de_id')->nullable()->constrained('users')->nullOnDelete();   // quién se las dio (código)
            $table->string('llaves_destino', 10)->nullable(); // oficina | usuario (a dónde quedaron)
            $table->timestamps();
        });

        Schema::create('vehiculo_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('vehiculo_movimientos')->cascadeOnDelete();
            $table->string('clave', 30);                      // tablero | frente | ... | llaves | dano
            $table->string('ruta');
            $table->string('ruta_thumb')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('licencia_numero', 40)->nullable();
            $table->date('licencia_vence')->nullable();
            $table->string('licencia_foto')->nullable();
        });

        $ahora = now();
        DB::table('vehiculo_tipos')->insert([
            [
                'nombre' => 'Moto', 'icono' => 'bicycle', 'licencia_tipo' => 'Motociclista', 'servicio_cada_km' => 3000, 'activo' => true,
                'fotos_requeridas' => json_encode([
                    ['clave' => 'frente', 'etiqueta' => 'Frente'], ['clave' => 'atras', 'etiqueta' => 'Atrás'],
                    ['clave' => 'izquierda', 'etiqueta' => 'Izquierda'], ['clave' => 'derecha', 'etiqueta' => 'Derecha'],
                ]),
                'checklist' => json_encode(['Casco', 'Tarjeta de circulación', 'Póliza de seguro']),
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
            [
                'nombre' => 'Coche', 'icono' => 'car', 'licencia_tipo' => 'Automovilista', 'servicio_cada_km' => 10000, 'activo' => true,
                'fotos_requeridas' => json_encode([
                    ['clave' => 'frente', 'etiqueta' => 'Frente'], ['clave' => 'atras', 'etiqueta' => 'Atrás'],
                    ['clave' => 'izquierda', 'etiqueta' => 'Izquierda'], ['clave' => 'derecha', 'etiqueta' => 'Derecha'],
                    ['clave' => 'interior', 'etiqueta' => 'Interior'],
                ]),
                'checklist' => json_encode(['Tarjeta de circulación', 'Póliza de seguro', 'Llanta de refacción', 'Gato', 'Triángulos']),
                'created_at' => $ahora, 'updated_at' => $ahora,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['licencia_numero', 'licencia_vence', 'licencia_foto']);
        });
        Schema::dropIfExists('vehiculo_fotos');
        Schema::dropIfExists('vehiculo_movimientos');
        Schema::dropIfExists('vehiculo_reservas');
        Schema::dropIfExists('vehiculos');
        Schema::dropIfExists('vehiculo_tipos');
    }
};
