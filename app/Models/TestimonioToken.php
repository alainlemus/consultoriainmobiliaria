<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TestimonioToken extends Model
{
    protected $fillable = [
        'token', 'expediente_id', 'email_destino', 'nombre_destino',
        'expires_at', 'usado_at', 'enviado_por',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'usado_at'   => 'datetime',
    ];

    // ─── Relaciones ────────────────────────────────────────────────────────────

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function enviador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    /** Token no usado y no expirado */
    public function scopeValido($query)
    {
        return $query->whereNull('usado_at')->where('expires_at', '>', now());
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function esValido(): bool
    {
        return is_null($this->usado_at) && $this->expires_at->isFuture();
    }

    public function marcarUsado(): void
    {
        $this->update(['usado_at' => now()]);
    }

    public static function generar(Expediente $expediente, int $enviadoPor): self
    {
        return self::create([
            'token'          => Str::random(48),
            'expediente_id'  => $expediente->id,
            'email_destino'  => $expediente->acreditado_email,
            'nombre_destino' => $expediente->acreditado_nombre,
            'expires_at'     => now()->addDays(7),
            'enviado_por'    => $enviadoPor,
        ]);
    }
}
