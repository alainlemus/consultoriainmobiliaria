<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cobertura extends Model
{
    protected $fillable = ['nombre', 'descripcion', 'detalle', 'activo', 'orden'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
