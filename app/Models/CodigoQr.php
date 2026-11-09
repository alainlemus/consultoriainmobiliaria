<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * QR impreso de un asesor en un medio concreto (lona, tarjeta, panfleto…).
 * La liga del QR es /a/{codigo}; el código también se imprime en texto para
 * que el acreditado pueda escribirlo al registrarse si no puede escanear.
 */
class CodigoQr extends Model
{
    protected $table = 'codigos_qr';

    protected $fillable = ['asesor_id', 'codigo', 'medio', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public const MEDIOS = [
        'todos'    => 'Todos los medios',   // un solo QR por asesor para lona, tarjeta, volante…
        'lona'     => 'Lona',
        'tarjeta'  => 'Tarjeta de presentación',
        'panfleto' => 'Panfleto / volante',
        'anuncio'  => 'Anuncio pegado',
        'otro'     => 'Otro',
    ];

    /** Sin 0/O ni 1/I para que se lea bien impreso. */
    private const ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function escaneos(): HasMany
    {
        return $this->hasMany(EscaneoQr::class, 'codigo_qr_id');
    }

    /** Cuentas de la app creadas con este código. */
    public function acreditados(): HasMany
    {
        return $this->hasMany(Acreditado::class, 'codigo_qr_id');
    }

    /** Prospectos (solicitudes de asesoría) que llegaron por este código. */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class, 'codigo_qr_id');
    }

    /** Expedientes abiertos a partir de esos prospectos: el número que paga la lona. */
    public function expedientes(): HasManyThrough
    {
        return $this->hasManyThrough(Expediente::class, Contacto::class, 'codigo_qr_id', 'contacto_id');
    }

    // ── Scopes / helpers ──────────────────────────────────────────────────────

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Normaliza lo que teclea la persona: mayúsculas, sin espacios ni guiones. */
    public static function normalizar(?string $codigo): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $codigo));
    }

    /** Busca un código activo tolerando minúsculas, espacios y guiones. */
    public static function buscarActivo(?string $codigo): ?self
    {
        $codigo = static::normalizar($codigo);
        if ($codigo === '') return null;

        return static::activos()->where('codigo', $codigo)->first();
    }

    /** Genera un código único de 6 caracteres legibles. */
    public static function generarCodigo(): string
    {
        do {
            $codigo = '';
            for ($i = 0; $i < 6; $i++) {
                $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
            }
        } while (static::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /** Liga corta que va dentro del QR. */
    public function getUrlAttribute(): string
    {
        return route('qr.asesor', ['codigo' => $this->codigo]);
    }

    public function getMedioLabelAttribute(): string
    {
        return self::MEDIOS[$this->medio] ?? ucfirst((string) $this->medio);
    }
}
