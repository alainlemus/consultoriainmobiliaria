<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    public const SOLICITADA = 'solicitada';
    public const CONFIRMADA = 'confirmada';
    public const CANCELADA  = 'cancelada';
    public const REALIZADA  = 'realizada';
    public const NO_ASISTIO = 'no_asistio';

    /** Ocupan el calendario del super admin. */
    public const ACTIVAS = [self::SOLICITADA, self::CONFIRMADA];

    public const ESTADOS = [
        self::SOLICITADA => 'Solicitada',
        self::CONFIRMADA => 'Confirmada',
        self::CANCELADA  => 'Cancelada',
        self::REALIZADA  => 'Realizada',
        self::NO_ASISTIO => 'No asistió',
    ];

    protected $fillable = [
        'contacto_id', 'expediente_id', 'acreditado_id', 'asesor_id', 'atiende_id', 'cobertura_id',
        'tipo', 'direccion', 'nombre', 'telefono', 'inicia_at', 'termina_at', 'estado',
        'creada_por_tipo', 'creada_por_id', 'notas', 'motivo_cancelacion',
        'confirmada_at', 'reagendada_at', 'recordatorio_dia_at', 'recordatorio_horas_at',
    ];

    protected $casts = [
        'inicia_at'             => 'datetime',
        'termina_at'            => 'datetime',
        'confirmada_at'         => 'datetime',
        'reagendada_at'         => 'datetime',
        'recordatorio_dia_at'   => 'datetime',
        'recordatorio_horas_at' => 'datetime',
    ];

    public function contacto(): BelongsTo   { return $this->belongsTo(Contacto::class); }
    public function expediente(): BelongsTo { return $this->belongsTo(Expediente::class); }
    public function acreditado(): BelongsTo { return $this->belongsTo(Acreditado::class); }
    public function asesor(): BelongsTo     { return $this->belongsTo(User::class, 'asesor_id'); }
    public function atiende(): BelongsTo    { return $this->belongsTo(User::class, 'atiende_id'); }
    public function cobertura(): BelongsTo  { return $this->belongsTo(Cobertura::class); }

    public function scopeActivas(Builder $q): Builder
    {
        return $q->whereIn('estado', self::ACTIVAS);
    }

    public function estaActiva(): bool
    {
        return in_array($this->estado, self::ACTIVAS, true);
    }

    /** Lugar legible: oficina con dirección o domicilio del acreditado. */
    public function lugar(): string
    {
        if ($this->tipo === 'domicilio') return 'Domicilio: ' . ($this->direccion ?: 'por confirmar');
        $c = $this->cobertura;
        return 'Oficina ' . ($c?->nombre ?? '') . ($c?->detalle ? " · {$c->detalle}" : '');
    }
}
