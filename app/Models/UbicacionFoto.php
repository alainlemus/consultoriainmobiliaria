<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UbicacionFoto extends Model
{
    protected $table = 'ubicacion_fotos';

    protected $fillable = ['ubicacion_id', 'ruta', 'mime'];

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }
}
