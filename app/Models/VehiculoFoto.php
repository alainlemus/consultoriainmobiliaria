<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class VehiculoFoto extends Model
{
    protected $table = 'vehiculo_fotos';

    protected $fillable = ['movimiento_id', 'clave', 'ruta', 'ruta_thumb'];

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(VehiculoMovimiento::class, 'movimiento_id');
    }

    /** URL firmada; la expiración se redondea a la hora para que el navegador/app reutilice la copia. */
    public function url(bool $thumb = false): string
    {
        $params = ['fotoId' => $this->id] + ($thumb ? ['thumb' => 1] : []);

        return URL::signedRoute('api.vehiculo.foto', $params, now()->addHour()->startOfHour());
    }
}
