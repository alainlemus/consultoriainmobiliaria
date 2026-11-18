<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tipo de vehículo (Moto, Coche…): define qué fotos y checklist se piden en cada salida y entrega. */
class VehiculoTipo extends Model
{
    protected $table = 'vehiculo_tipos';

    protected $fillable = ['nombre', 'icono', 'fotos_requeridas', 'checklist', 'licencia_tipo', 'servicio_cada_km', 'activo'];

    protected $casts = [
        'fotos_requeridas' => 'array',
        'checklist' => 'array',
        'activo' => 'boolean',
    ];

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    /** Fotos obligatorias en salida y entrega: el tablero siempre, más las del tipo. @return list<array{clave:string, etiqueta:string}> */
    public function fotosObligatorias(): array
    {
        $extra = collect($this->fotos_requeridas ?? [])
            ->filter(fn ($f) => ! empty($f['clave']) && $f['clave'] !== 'tablero')
            ->map(fn ($f) => ['clave' => (string) $f['clave'], 'etiqueta' => (string) ($f['etiqueta'] ?? $f['clave'])])
            ->values()->all();

        return [['clave' => 'tablero', 'etiqueta' => 'Tablero (km y gasolina)'], ...$extra];
    }
}
