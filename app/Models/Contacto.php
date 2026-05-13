<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contacto extends Model
{
    protected $fillable = [
        'nombre', 'telefono', 'email', 'servicio', 'mensaje', 'estado', 'notas',
        // CRM
        'asesor_id', 'origen', 'curp', 'fecha_nacimiento', 'antiguedad_laboral',
        'salario_mensual', 'tipo_credito_interes', 'monto_credito_estimado',
        'subcuenta_vivienda', 'notas_precalificacion', 'estado_prospecto',
        'fecha_primer_contacto',
    ];

    protected $casts = [
        'fecha_nacimiento'       => 'date',
        'fecha_primer_contacto'  => 'date',
        'salario_mensual'        => 'decimal:2',
        'monto_credito_estimado' => 'decimal:2',
        'subcuenta_vivienda'     => 'decimal:2',
    ];

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }
}
