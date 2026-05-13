<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoRequerido extends Model
{
    protected $fillable = [
        'tipo_tramite_id', 'nombre', 'seccion', 'descripcion', 'obligatorio', 'orden',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
    ];

    public function tipoTramite(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class);
    }
}
