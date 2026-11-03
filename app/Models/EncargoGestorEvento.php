<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Historial del ida y vuelta de un encargo (envío, revisión, reenvío, subidas…). */
class EncargoGestorEvento extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'encargo_gestor_eventos';

    protected $fillable = ['encargo_gestor_id', 'user_id', 'tipo', 'detalle'];

    public function encargo(): BelongsTo
    {
        return $this->belongsTo(EncargoGestor::class, 'encargo_gestor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
