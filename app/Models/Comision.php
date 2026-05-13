<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comision extends Model
{
    protected $fillable = [
        'expediente_id', 'asesor_id', 'monto_base', 'porcentaje_comision',
        'monto_comision', 'estado', 'fecha_generacion', 'fecha_aprobacion',
        'fecha_pago', 'aprobado_por', 'notas',
    ];

    protected $casts = [
        'fecha_generacion'  => 'date',
        'fecha_aprobacion'  => 'date',
        'fecha_pago'        => 'date',
        'monto_base'        => 'decimal:2',
        'porcentaje_comision' => 'decimal:2',
        'monto_comision'    => 'decimal:2',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}
