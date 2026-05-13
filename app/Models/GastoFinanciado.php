<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoFinanciado extends Model
{
    protected $fillable = [
        'expediente_id', 'concepto', 'monto', 'fecha_pago',
        'comprobante_ruta', 'comprobante_nombre', 'estado', 'registrado_por', 'notas',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto'      => 'decimal:2',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
