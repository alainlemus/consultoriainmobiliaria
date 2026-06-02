<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappConversation extends Model
{
    protected $fillable = [
        'chat_id',
        'telefono',
        'paso',
        'datos',
        'ultimo_mensaje_at',
    ];

    protected $casts = [
        'datos'              => 'array',
        'ultimo_mensaje_at'  => 'datetime',
    ];

    /**
     * Obtener un valor del JSON datos.
     */
    public function getDato(string $key, mixed $default = null): mixed
    {
        return $this->datos[$key] ?? $default;
    }

    /**
     * Actualizar un valor en el JSON datos.
     */
    public function setDato(string $key, mixed $value): void
    {
        $datos = $this->datos ?? [];
        $datos[$key] = $value;
        $this->datos = $datos;
    }

    /**
     * Verificar si la conversación expiró (más de 30 min sin actividad).
     */
    public function expirada(): bool
    {
        if (! $this->ultimo_mensaje_at) return true;
        return $this->ultimo_mensaje_at->diffInMinutes(now()) >= 30;
    }
}
