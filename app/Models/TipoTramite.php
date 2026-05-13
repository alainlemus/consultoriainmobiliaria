<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoTramite extends Model
{
    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'porcentaje_honorarios', 'activo', 'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'porcentaje_honorarios' => 'decimal:2',
    ];

    public function etapas(): HasMany
    {
        return $this->hasMany(EtapaTramite::class)->orderBy('orden');
    }

    public function documentosRequeridos(): HasMany
    {
        return $this->hasMany(DocumentoRequerido::class)->orderBy('orden');
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }
}
