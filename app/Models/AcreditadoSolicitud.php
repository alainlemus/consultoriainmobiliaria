<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcreditadoSolicitud extends Model
{
    protected $table = 'acreditado_solicitudes';

    protected $fillable = [
        'acreditado_id',
        'contacto_id',
        'tipo_tramite_id',
        'servicio',
        'mensaje',
        'municipio',
        'estado',
        'estado_solicitud',
    ];

    public function acreditado(): BelongsTo
    {
        return $this->belongsTo(Acreditado::class);
    }

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }

    public function tipoTramite(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class);
    }
}
