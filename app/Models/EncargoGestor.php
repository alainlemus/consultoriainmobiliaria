<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Encargo que la administración le hace a un gestor sobre un expediente.
 * Ver la migración create_encargos_gestor_tables para el flujo de estados.
 */
class EncargoGestor extends Model
{
    public const POR_REVISAR       = 'por_revisar';
    public const CON_OBSERVACIONES = 'con_observaciones';
    public const EN_TRAMITE        = 'en_tramite';
    public const TERMINADO         = 'terminado';
    public const CANCELADO         = 'cancelado';

    public const ESTADOS = [
        self::POR_REVISAR       => 'Por revisar',
        self::CON_OBSERVACIONES => 'Con observaciones',
        self::EN_TRAMITE        => 'En trámite',
        self::TERMINADO         => 'Terminado',
        self::CANCELADO         => 'Cancelado',
    ];

    /** Secciones de datos del expediente que el admin puede compartir con el gestor */
    public const SECCIONES_DATOS = [
        'acreditado' => 'Datos del acreditado',
        'vendedor'   => 'Datos del vendedor',
        'inmueble'   => 'Datos del inmueble',
        'credito'    => 'Datos del crédito',
    ];

    protected $table = 'encargos_gestor';

    protected $fillable = [
        'expediente_id', 'gestor_id', 'enviado_por', 'instrucciones', 'fecha_entrega', 'datos_compartidos',
        'estado', 'vuelta', 'tramite_iniciado_at', 'terminado_at',
    ];

    protected $casts = [
        'fecha_entrega'       => 'date',
        'datos_compartidos'   => 'array',
        'tramite_iniciado_at' => 'datetime',
        'terminado_at'        => 'datetime',
        'vuelta'              => 'integer',
    ];

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }

    public function gestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gestor_id');
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    /** Documentos del expediente compartidos con el gestor, con su revisión */
    public function documentos(): BelongsToMany
    {
        return $this->belongsToMany(DocumentoExpediente::class, 'encargo_gestor_documento', 'encargo_gestor_id', 'documento_expediente_id')
            ->withPivot(['id', 'revision', 'nota', 'vuelta', 'revisado_at'])
            ->withTimestamps();
    }

    /** Documentos que subió el gestor por este encargo */
    public function documentosSubidos(): HasMany
    {
        return $this->hasMany(DocumentoExpediente::class, 'encargo_gestor_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EncargoGestorEvento::class)->orderBy('created_at')->orderBy('id');
    }

    public function scopeActivos($query)
    {
        return $query->whereNotIn('estado', [self::TERMINADO, self::CANCELADO]);
    }

    /**
     * Datos del expediente que el admin compartió, listos para mostrar.
     * @return array<int, array{clave:string, titulo:string, campos:array<int, array{label:string, valor:string}>}>
     */
    public function datosExpediente(): array
    {
        $x = $this->expediente;
        $une = fn (...$p) => trim(implode(', ', array_filter($p, fn ($v) => filled($v))));
        $money = fn ($v) => filled($v) ? '$' . number_format((float) $v, 2) : null;

        $secciones = [
            'acreditado' => [
                'Nombre'          => $x->acreditado_nombre,
                'CURP'            => $x->acreditado_curp,
                'RFC'             => $x->acreditado_rfc,
                'Teléfono'        => $x->acreditado_telefono,
                'Correo'          => $x->acreditado_email,
                'Domicilio'       => $une($x->acreditado_domicilio, $x->acreditado_colonia, $x->acreditado_municipio, $x->acreditado_estado, $x->acreditado_cp),
                'Estado civil'    => $x->acreditado_estado_civil,
                'Número de crédito' => $x->acreditado_numero_credito,
                'Cónyuge'         => $x->conyuge_nombre,
            ],
            'vendedor' => [
                'Nombre'    => $x->vendedor_nombre,
                'CURP'      => $x->vendedor_curp,
                'RFC'       => $x->vendedor_rfc,
                'Teléfono'  => $x->vendedor_telefono,
                'Domicilio' => $x->vendedor_domicilio,
            ],
            'inmueble' => [
                'Dirección'   => $une($x->vivienda_calle . ' ' . $x->vivienda_numero, $x->vivienda_colonia, $x->vivienda_municipio, $x->vivienda_estado, $x->vivienda_cp),
                'Tipo'        => $x->vivienda_tipo,
                'Superficie'  => filled($x->vivienda_superficie) ? $x->vivienda_superficie . ' m²' : null,
                'Descripción en escritura' => $x->vivienda_descripcion_titulo,
                'CUV'         => $x->cuv,
            ],
            'credito' => [
                'Trámite'       => $x->tipoTramite?->nombre,
                'Uso del crédito' => $x->uso_credito,
                'Modalidad'     => $x->modalidad_credito,
                'Monto del crédito' => $money($x->monto_credito),
            ],
        ];

        $out = [];
        foreach ($this->datos_compartidos ?? [] as $clave) {
            if (! isset($secciones[$clave])) {
                continue;
            }
            $campos = collect($secciones[$clave])
                ->filter(fn ($v) => filled($v) && trim((string) $v) !== '')
                ->map(fn ($v, $label) => ['label' => $label, 'valor' => trim((string) $v)])
                ->values()->all();
            $out[] = ['clave' => $clave, 'titulo' => self::SECCIONES_DATOS[$clave], 'campos' => $campos];
        }

        return $out;
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
