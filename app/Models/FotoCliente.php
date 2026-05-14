<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoCliente extends Model
{
    protected $fillable = [
        'foto',
        'nombre',
        'tipo_credito',
        'ciudad',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden')->orderBy('created_at', 'desc');
    }
}
