<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anuncio extends Model
{
    protected $table = 'anuncios';

    protected $fillable = [
        'user_id',
        'latitud',
        'longitud',
        'tipo',
        'estado',
        'descripcion',
        'direccion',
        'colonia',
        'municipio',
        'estado_geo',
        'colocado_en',
    ];

    protected $casts = [
        'latitud'     => 'float',
        'longitud'    => 'float',
        'colocado_en' => 'date',
    ];

    /** Tipos disponibles con sus etiquetas y emojis */
    public const TIPOS = [
        'lona'         => ['label' => 'Lona',              'emoji' => '📢'],
        'hoja_tienda'  => ['label' => 'Hoja en tienda',    'emoji' => '🏪'],
        'hoja_poste'   => ['label' => 'Hoja en poste',     'emoji' => '📌'],
        'volante'      => ['label' => 'Volante / reparto', 'emoji' => '📄'],
        'otro'         => ['label' => 'Otro',              'emoji' => '📣'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(AnuncioFoto::class);
    }
}
