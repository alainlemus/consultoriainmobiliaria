<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Contacto extends Model
{
    protected $fillable = [
        'nombre', 'telefono', 'email', 'servicio', 'mensaje', 'estado', 'notas',
        // CRM
        'asesor_id', 'origen', 'curp', 'nss', 'foto',
        // Escuela vinculada (maestros FOVISSSTE)
        'escuela_id',
        // Precalificación (FOVISSSTE / INFONAVIT)
        'estado_uso_credito', 'municipio_uso_credito', 'estado_residencia',
        'regimen_pensionario', 'tiene_discapacidad', 'simulador_screenshot',
        // Estado y fechas
        'estado_prospecto', 'estado_ubicacion',
        'fecha_nacimiento', 'fecha_primer_contacto', 'modalidad_cierre', 'notas_cierre', 'fecha_envio_dueno',
    ];

    protected $appends = ['foto_url', 'simulador_screenshot_url'];

    protected $attributes = [
        'estado_prospecto' => 'nuevo',
    ];

    protected $casts = [
        'fecha_nacimiento'      => 'date',
        'fecha_primer_contacto' => 'date',
        'fecha_envio_dueno'     => 'datetime',
        'tiene_discapacidad'    => 'boolean',
    ];

    // Normaliza servicio a mayúsculas al leer (datos legacy en minúsculas)
    public function getServicioAttribute(?string $value): ?string
    {
        return $value ? strtoupper($value) : null;
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    public function getSimuladorScreenshotUrlAttribute(): ?string
    {
        return $this->simulador_screenshot
            ? Storage::disk('public')->url($this->simulador_screenshot)
            : null;
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /** Escuela a la que pertenece este prospecto (solo maestros) */
    public function escuela(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'escuela_id');
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class);
    }

    /**
     * Busca un prospecto existente por teléfono o CURP (los dos campos que
     * identifican a la misma persona a través de los distintos canales de
     * captura: sitio web, app móvil, panel admin, solicitud de acreditado).
     *
     * @param  int|null  $ignorarId  Excluir este id (para no chocar consigo mismo al editar).
     */
    public static function buscarDuplicado(?string $telefono, ?string $curp, ?int $ignorarId = null): ?self
    {
        $telefono = $telefono ? trim($telefono) : null;
        $curp     = $curp ? strtoupper(trim($curp)) : null;

        if (! $telefono && ! $curp) {
            return null;
        }

        return static::query()
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->where(function ($q) use ($telefono, $curp) {
                if ($telefono) {
                    $q->orWhere('telefono', $telefono);
                }
                if ($curp) {
                    $q->orWhere('curp', $curp);
                }
            })
            ->first();
    }
}
