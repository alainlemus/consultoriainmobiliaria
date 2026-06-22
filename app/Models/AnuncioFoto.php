<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnuncioFoto extends Model
{
    protected $table = 'anuncio_fotos';

    protected $fillable = ['anuncio_id', 'ruta', 'mime'];

    public function anuncio(): BelongsTo
    {
        return $this->belongsTo(Anuncio::class);
    }
}
