<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Un checklist de salida o de entrega, o un cambio de manos de las llaves. */
class VehiculoMovimiento extends Model
{
    public const SALIDA = 'salida';

    public const ENTREGA = 'entrega';

    public const LLAVES = 'llaves';

    public const RESGUARDO = 'resguardo';   // guardado en casa del asesor

    public const RETOMAR = 'retomar';     // lo vuelve a usar a la mañana siguiente

    public const CARGA = 'carga';       // carga de gasolina (guarda las fotos del ticket y tablero)

    protected $table = 'vehiculo_movimientos';

    protected $fillable = [
        'vehiculo_id', 'reserva_id', 'user_id', 'tipo', 'km', 'gasolina', 'lat', 'lng',
        'notas_danos', 'checklist', 'llaves_modo', 'llaves_de_id', 'llaves_destino',
    ];

    protected $casts = [
        'checklist' => 'array',
        'km' => 'integer',
        'gasolina' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(VehiculoReserva::class, 'reserva_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function llavesDe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'llaves_de_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(VehiculoFoto::class, 'movimiento_id');
    }
}
