<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Carga de gasolina pagada por la empresa (tarjeta o efectivo) o por el asesor con reembolso. */
class VehiculoCarga extends Model
{
    public const PAGOS = [
        'tarjeta_empresa' => 'Tarjeta de la empresa',
        'efectivo_empresa' => 'Efectivo de la empresa',
        'reembolso' => 'Pagó el asesor (reembolso)',
    ];

    protected $table = 'vehiculo_cargas';

    protected $fillable = ['reserva_id', 'vehiculo_id', 'user_id', 'movimiento_id', 'km', 'litros', 'monto', 'pago', 'reembolsado_at', 'reembolsado_por'];

    protected $casts = [
        'km' => 'integer',
        'litros' => 'decimal:2',
        'monto' => 'decimal:2',
        'reembolsado_at' => 'datetime',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(VehiculoReserva::class, 'reserva_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(VehiculoMovimiento::class, 'movimiento_id');
    }

    public function reembolsadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reembolsado_por');
    }

    public function reembolsoEstado(): ?string
    {
        if ($this->pago !== 'reembolso') {
            return null;
        }

        return $this->reembolsado_at ? 'pagado' : 'pendiente';
    }
}
