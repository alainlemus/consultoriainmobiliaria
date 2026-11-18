<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    public const ACTIVO = 'activo';

    public const TALLER = 'taller';

    public const BAJA = 'baja';

    public const ESTADOS = [self::ACTIVO => 'Activo', self::TALLER => 'En taller', self::BAJA => 'Dado de baja'];

    public const GASOLINA = [0 => 'Reserva', 1 => '¼', 2 => '½', 3 => '¾', 4 => 'Lleno'];

    protected $fillable = [
        'vehiculo_tipo_id', 'nombre', 'placa', 'marca', 'modelo', 'anio', 'color', 'foto',
        'capacidad_tanque_l', 'km_actual', 'gasolina_actual', 'km_proximo_servicio',
        'vence_seguro', 'vence_tarjeta', 'estado', 'llaves_en', 'llaves_user_id',
        'llaves_codigo', 'llaves_codigo_user_id', 'llaves_codigo_expira_at', 'notas',
    ];

    protected $hidden = ['llaves_codigo'];

    protected $casts = [
        'vence_seguro' => 'date',
        'vence_tarjeta' => 'date',
        'llaves_codigo_expira_at' => 'datetime',
        'km_actual' => 'integer',
        'gasolina_actual' => 'integer',
    ];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(VehiculoTipo::class, 'vehiculo_tipo_id');
    }

    public function llavesUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'llaves_user_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(VehiculoReserva::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(VehiculoMovimiento::class);
    }

    public function etiqueta(): string
    {
        return trim($this->nombre.($this->placa ? " · {$this->placa}" : ''));
    }
}
