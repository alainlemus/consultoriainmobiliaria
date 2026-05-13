<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoExpediente extends Model
{
    protected $fillable = [
        'expediente_id', 'documento_requerido_id', 'nombre', 'seccion', 'estado',
        'ruta_archivo', 'nombre_archivo', 'observaciones', 'subido_por', 'fecha_entrega',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function documentoRequerido(): BelongsTo
    {
        return $this->belongsTo(DocumentoRequerido::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
