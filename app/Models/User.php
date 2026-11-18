<?php

namespace App\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPanelShield, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
        'banco',
        'clabe',
        'telefono',
        'foto_perfil',
        'licencia_numero',
        'licencia_vence',
        'licencia_foto',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'licencia_vence' => 'date',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->activo) {
            return false;
        }

        return $this->roles()->exists();
    }

    /** Encargos recibidos como gestor */
    public function encargosGestor(): HasMany
    {
        return $this->hasMany(EncargoGestor::class, 'gestor_id');
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'asesor_id');
    }

    /** Prospectos asignados al asesor. */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class, 'asesor_id');
    }

    /** Documentos de los expedientes del asesor (los que sube desde la app). */
    public function documentos(): HasManyThrough
    {
        return $this->hasManyThrough(DocumentoExpediente::class, Expediente::class, 'asesor_id');
    }

    public function routePoints(): HasMany
    {
        return $this->hasMany(RoutePoint::class);
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class);
    }

    /** Licencia de manejo cargada y sin vencer. Solo genera avisos; no impide usar vehículos. */
    public function licenciaVigente(): bool
    {
        return $this->licencia_vence !== null && $this->licencia_vence->endOfDay()->isFuture();
    }

    public function getFotoPerfilUrlAttribute(): ?string
    {
        if (! $this->foto_perfil) {
            return null;
        }

        return URL::signedRoute('api.user.foto', ['user' => $this->id], now()->addHours(1));
    }
}
