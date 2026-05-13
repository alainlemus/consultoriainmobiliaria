<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoExpediente extends Model
{
    protected $fillable = [
        'expediente_id', 'usuario_id', 'tipo', 'descripcion',
        'etapa_anterior_id', 'etapa_nueva_id',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function etapaAnterior(): BelongsTo
    {
        return $this->belongsTo(EtapaTramite::class, 'etapa_anterior_id');
    }

    public function etapaNueva(): BelongsTo
    {
        return $this->belongsTo(EtapaTramite::class, 'etapa_nueva_id');
    }
}
