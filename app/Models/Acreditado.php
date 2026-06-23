<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

class Acreditado extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'curp',
        'telefono',
        'nss',
        'rfc',
        'foto_perfil',
        'contacto_id',
        'activo',
        'curp_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'         => 'hashed',
            'activo'           => 'boolean',
            'curp_verified_at' => 'datetime',
            'email_verified_at'=> 'datetime',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** El contacto/prospecto vinculado en el CRM */
    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }

    /** Expedientes donde este acreditado está registrado */
    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'acreditado_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Foto de perfil con URL firmada de 1 hora */
    public function getFotoPerfilUrlAttribute(): ?string
    {
        if (! $this->foto_perfil) return null;
        return URL::signedRoute(
            'api.acreditado.foto-perfil',
            ['acreditado' => $this->id],
            now()->addHour()
        );
    }

    /**
     * Busca y vincula el expediente más reciente que coincida con el CURP.
     * Retorna el expediente vinculado o null si no encuentra.
     */
    public function vincularExpedientePorCurp(): ?Expediente
    {
        if (! $this->curp) return null;

        $expediente = Expediente::where('acreditado_curp', $this->curp)
            ->whereNull('acreditado_id')
            ->latest()
            ->first();

        if ($expediente) {
            $expediente->update(['acreditado_id' => $this->id]);
            $this->update(['curp_verified_at' => now()]);

            // Si hay contacto con esa CURP, vincularlo también
            $contacto = Contacto::where('curp', $this->curp)->latest()->first();
            if ($contacto && ! $this->contacto_id) {
                $this->update(['contacto_id' => $contacto->id]);
            }
        }

        return $expediente;
    }

    /** Payload de respuesta para la API */
    public function toApiArray(): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'telefono'          => $this->telefono,
            'curp'              => $this->curp,
            'nss'               => $this->nss,
            'rfc'               => $this->rfc,
            'foto_perfil_url'   => $this->foto_perfil_url,
            'curp_verificado'   => (bool) $this->curp_verified_at,
            'tiene_expediente'  => $this->expedientes()->exists(),
        ];
    }
}
