<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    protected $fillable = [
        'user_id',
        'contacto_id',
        'latitud',
        'longitud',
        'tipo',
        'nombre_lugar',
        'direccion',
        'notas',
        'municipio',
        'estado',
        'visitado_en',
    ];

    protected $casts = [
        'latitud'     => 'float',
        'longitud'    => 'float',
        'visitado_en' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(UbicacionFoto::class);
    }
}
