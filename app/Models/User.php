<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasPanelShield, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
        'banco',
        'clabe',
        'telefono',
        'foto_perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->activo) {
            return false;
        }

        return $this->roles()->exists();
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'asesor_id');
    }

    public function getFotoPerfilUrlAttribute(): ?string
    {
        if (! $this->foto_perfil) {
            return null;
        }

        return URL::signedRoute('api.user.foto', ['user' => $this->id], now()->addHours(1));
    }
}
