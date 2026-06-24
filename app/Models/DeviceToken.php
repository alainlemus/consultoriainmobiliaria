<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'tokenable_type',
        'tokenable_id',
        'fcm_token',
        'plataforma',
        'ultimo_uso',
    ];

    protected $casts = [
        'ultimo_uso' => 'datetime',
    ];

    /** Relación polimórfica: puede ser User o Acreditado */
    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Backwards compat — asesores/admins siguen usando user() */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
