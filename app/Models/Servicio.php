<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $fillable = ['titulo', 'items', 'wa_texto', 'icon_path', 'activo', 'orden'];

    protected $casts = [
        'items'  => 'array',
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
