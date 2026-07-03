<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutePoint extends Model
{
    protected $table = 'route_points';

    protected $fillable = [
        'user_id',
        'lat',
        'lng',
        'precision',
        'velocidad',
        'timestamp',
        'synced_at',
    ];

    protected $casts = [
        'lat'       => 'float',
        'lng'       => 'float',
        'precision' => 'integer',
        'velocidad' => 'float',
        'timestamp' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
