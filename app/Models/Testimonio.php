<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonio extends Model
{
    protected $fillable = [
        'nombre', 'ciudad', 'servicio', 'testimonio', 'estrellas', 'activo', 'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->latest();
    }
}
