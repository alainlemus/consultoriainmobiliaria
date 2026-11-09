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

    /** Días como los guarda el formulario del panel (claves de texto) → día ISO. */
    public const DIAS = ['lun' => 1, 'mar' => 2, 'mie' => 3, 'jue' => 4, 'vie' => 5, 'sab' => 6, 'dom' => 7];

    /** Horario efectivo: lo configurado completado con los valores por defecto; `dias` siempre en ISO (1 = lunes). */
    public function horario(): array
    {
        $h = array_merge(self::HORARIO_DEFAULT, array_filter((array) $this->horario_citas, fn ($v) => $v !== null && $v !== '' && $v !== []));
        $h['dias'] = self::diasIso($h['dias']);

        return $h;
    }

    /** Acepta ['lun','mar'] o [1,2] y devuelve [1,2]. */
    public static function diasIso(array $dias): array
    {
        return collect($dias)
            ->map(fn ($d) => is_numeric($d) ? (int) $d : (self::DIAS[$d] ?? null))
            ->filter()->unique()->sort()->values()->all();
    }

    /** Inverso: [1,2] → ['lun','mar'], para pintar las casillas del panel. */
    public static function diasClave(array $dias): array
    {
        $inv = array_flip(self::DIAS);
        return collect($dias)->map(fn ($d) => is_numeric($d) ? ($inv[(int) $d] ?? null) : $d)->filter()->values()->all();
    }

    public function citas()
    {
        return $this->hasMany(Cita::class);
    }
}
