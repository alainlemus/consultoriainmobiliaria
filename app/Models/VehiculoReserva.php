<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VehiculoReserva extends Model
{
    public const SOLICITADA = 'solicitada';

    public const EN_ESPERA = 'en_espera';

    public const AUTORIZADA = 'autorizada';

    public const EN_USO = 'en_uso';

    public const RESGUARDO = 'resguardo';     // en uso, guardado en casa del asesor durante la noche

    public const ENTREGADA = 'entregada';

    public const RECHAZADA = 'rechazada';

    public const CANCELADA = 'cancelada';

    public const NO_PRESENTO = 'no_presento';

    /** Ocupan el vehículo en su horario (la fila se arma con estas más las en espera). */
    public const OCUPAN = [self::SOLICITADA, self::AUTORIZADA, self::EN_USO, self::RESGUARDO];

    /** El vehículo está fuera (salió y no se ha entregado). */
    public const FUERA = [self::EN_USO, self::RESGUARDO];

    public const ESTADOS = [
        self::SOLICITADA => 'Pendiente de autorización',
        self::EN_ESPERA => 'En espera',
        self::AUTORIZADA => 'Autorizada',
        self::EN_USO => 'En uso',
        self::ENTREGADA => 'Entregada',
        self::RECHAZADA => 'Rechazada',
        self::CANCELADA => 'Cancelada',
        self::RESGUARDO => 'Guardado en casa',
        self::NO_PRESENTO => 'No se presentó',
    ];

    protected $table = 'vehiculo_reservas';

    protected $fillable = [
        'vehiculo_id', 'asesor_id', 'todo_el_dia', 'inicio', 'fin', 'resguardo_casa', 'motivo', 'destino',
        'cita_id', 'contacto_id', 'expediente_id', 'estado', 'decidido_por', 'decidido_at', 'motivo_decision',
        'desplazada_por_id', 'desplazada_at', 'desplazada_motivo', 'estado_previo', 'alertas',
        'recordatorio_salida_at', 'recordatorio_regreso_at', 'alerta_retraso_at',
    ];

    protected $casts = [
        'todo_el_dia' => 'boolean',
        'resguardo_casa' => 'boolean',
        'inicio' => 'datetime',
        'fin' => 'datetime',
        'decidido_at' => 'datetime',
        'desplazada_at' => 'datetime',
        'alertas' => 'array',
        'recordatorio_salida_at' => 'datetime',
        'recordatorio_regreso_at' => 'datetime',
        'alerta_retraso_at' => 'datetime',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function decididoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(VehiculoMovimiento::class, 'reserva_id');
    }

    public function cargas(): HasMany
    {
        return $this->hasMany(VehiculoCarga::class, 'reserva_id');
    }

    public function desplazadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desplazada_por_id');
    }

    /** El último «guardar en casa». */
    public function resguardo(): HasOne
    {
        return $this->hasOne(VehiculoMovimiento::class, 'reserva_id')->where('tipo', VehiculoMovimiento::RESGUARDO)->latestOfMany();
    }

    public function salida(): HasOne
    {
        return $this->hasOne(VehiculoMovimiento::class, 'reserva_id')->where('tipo', VehiculoMovimiento::SALIDA);
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(VehiculoMovimiento::class, 'reserva_id')->where('tipo', VehiculoMovimiento::ENTREGA);
    }

    /** Reservas cuyo horario se cruza con [inicio, fin). */
    public function scopeSeCruzaCon(Builder $q, $inicio, $fin): Builder
    {
        return $q->where('inicio', '<', $fin)->where('fin', '>', $inicio);
    }

    /** Agrega una alerta (diferencia detectada) sin repetirla. */
    public function alertar(string $texto): void
    {
        $alertas = $this->alertas ?? [];
        if (! in_array($texto, $alertas, true)) {
            $alertas[] = $texto;
            $this->update(['alertas' => $alertas]);
        }
    }

    public function estaFuera(): bool
    {
        return in_array($this->estado, self::FUERA, true);
    }

    public function estaAbierta(): bool
    {
        return in_array($this->estado, [self::SOLICITADA, self::EN_ESPERA, self::AUTORIZADA], true);
    }
}
