<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtapaTramite extends Model
{
    protected $fillable = [
        'tipo_tramite_id', 'nombre', 'descripcion', 'orden', 'color', 'es_final',
    ];

    protected $casts = [
        'es_final' => 'boolean',
    ];

    public function tipoTramite(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class);
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'etapa_tramite_id');
    }
}
