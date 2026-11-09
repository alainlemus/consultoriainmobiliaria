<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Zona de cobertura / oficina. Las que tienen `citas_activas` reciben citas
 * presenciales con el horario que configura el panel.
 */
class Cobertura extends Model
{
    protected $fillable = ['nombre', 'descripcion', 'detalle', 'activo', 'orden', 'citas_activas', 'horario_citas'];

    protected $casts = [
        'activo'        => 'boolean',
        'citas_activas' => 'boolean',
        'horario_citas' => 'array',
    ];

    /** Horario por defecto si el panel no ha configurado nada. */
    public const HORARIO_DEFAULT = [
        'dias'                   => [1, 2, 3, 4, 5],   // lunes a viernes (ISO: 1 = lunes)
        'inicio'                 => '09:00',
        'fin'                    => '17:00',
        'duracion_min'           => 60,
        'duracion_domicilio_min' => 120,
        'permite_domicilio'      => true,
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    public function scopeConCitas($query)
    {
        return $query->where('activo', true)->where('citas_activas', true)->orderBy('orden');
    }

    public function horario(): array
    {
        return array_merge(self::HORARIO_DEFAULT, array_filter((array) $this->horario_citas, fn ($v) => $v !== null && $v !== ''));
    }

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }
}
