<?php

namespace App\Models;

use App\Observers\ExpedienteObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expediente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folio',
        'tipo_tramite_id', 'etapa_tramite_id', 'asesor_id', 'contacto_id', 'revisor_id',
        'estado',
        // Acreditado
        'acreditado_nombre', 'acreditado_curp', 'acreditado_rfc', 'obligado_solidario_nombre', 'acreditado_fecha_nacimiento',
        'acreditado_telefono', 'acreditado_email', 'acreditado_domicilio', 'acreditado_colonia',
        'acreditado_municipio', 'acreditado_estado', 'acreditado_cp', 'acreditado_estado_civil',
        'acreditado_antiguedad_laboral', 'acreditado_numero_credito',
        'acreditado_personas_autorizadas', 'acreditado_referencias',
        // Portal FOVISSSTE (paso 4-J)
        'portal_fovissste_activado', 'portal_fovissste_notas',
        // Vendedor
        'vendedor_nombre', 'vendedor_curp', 'vendedor_rfc', 'vendedor_telefono', 'vendedor_email',
        'vendedor_domicilio', 'vendedor_requiere_acta_matrimonio', 'vendedor_banco', 'vendedor_clabe',
        'vendedor_exencion_isr', 'vendedor_requiere_avaluo_referido',
        // Vivienda
        'vivienda_calle', 'vivienda_numero', 'vivienda_colonia', 'vivienda_municipio',
        'vivienda_estado', 'vivienda_cp', 'vivienda_tipo', 'vivienda_superficie', 'vivienda_descripcion_titulo',
        // Catastro / subdivisión (paso 10)
        'requiere_subdivision', 'superficie_total_predio',
        // Trámite
        'uso_credito', 'modalidad_credito', 'banco_participante',
        'monto_credito', 'subcuenta_vivienda', 'monto_total_estimado',
        'honorarios_porcentaje', 'honorarios_monto', 'honorarios_pagados', 'fecha_pago_honorarios',
        'total_gastos_financiados', 'notas_internas', 'fecha_apertura', 'fecha_cierre',
        // Instrucción notarial (paso 18)
        'instruccion_notarial_recibida', 'instruccion_notarial_fecha',
        // CLG y notaría (paso 19)
        'clg_solicitado', 'clg_fecha_solicitud', 'clg_recibido', 'fecha_limite_firma', 'fecha_firma',
        // Guarda Valores y pago (paso 20)
        'fecha_envio_guarda_valores', 'fecha_esperada_pago', 'pago_recibido', 'fecha_pago_recibido',
        // CUV (pasos 14-15)
        'cuv', 'cuv_fecha_pago', 'cuv_activa',
        // OCR / IA
        'ocr_procesando',
        // Acreditado registrado en app
        'acreditado_id',
        // Cónyuge (crédito conyugal / mancomunado)
        'conyuge_nombre', 'conyuge_curp', 'conyuge_rfc', 'conyuge_telefono',
        'conyuge_institucion', 'conyuge_numero_credito',
        // Pensionado
        'numero_pension', 'clave_pension', 'fecha_inicio_pension', 'monto_pension_mensual',
    ];

    protected $casts = [
        'acreditado_personas_autorizadas'    => 'array',
        'acreditado_referencias'             => 'array',
        'acreditado_fecha_nacimiento'        => 'date',
        'honorarios_pagados'                 => 'boolean',
        'vendedor_requiere_acta_matrimonio'  => 'boolean',
        'vendedor_exencion_isr'              => 'boolean',
        'vendedor_requiere_avaluo_referido'  => 'boolean',
        'portal_fovissste_activado'          => 'boolean',
        'requiere_subdivision'               => 'boolean',
        'cuv_activa'                         => 'boolean',
        'ocr_procesando'                     => 'boolean',
        'instruccion_notarial_recibida'      => 'boolean',
        'clg_solicitado'                     => 'boolean',
        'clg_recibido'                       => 'boolean',
        'pago_recibido'                      => 'boolean',
        'fecha_pago_honorarios'              => 'date',
        'fecha_apertura'                     => 'date',
        'fecha_cierre'                       => 'date',
        'fecha_inicio_pension'               => 'date',
        'cuv_fecha_pago'                     => 'date',
        'instruccion_notarial_fecha'         => 'date',
        'clg_fecha_solicitud'                => 'date',
        'fecha_limite_firma'                 => 'date',
        'fecha_firma'                        => 'date',
        'fecha_envio_guarda_valores'         => 'date',
        'fecha_esperada_pago'                => 'date',
        'fecha_pago_recibido'                => 'date',
        'vivienda_superficie'                => 'decimal:2',
        'superficie_total_predio'            => 'decimal:2',
        'monto_credito'                      => 'decimal:2',
        'subcuenta_vivienda'                 => 'decimal:2',
        'monto_total_estimado'               => 'decimal:2',
        'honorarios_porcentaje'              => 'decimal:2',
        'honorarios_monto'                   => 'decimal:2',
        'total_gastos_financiados'           => 'decimal:2',
        'monto_pension_mensual'              => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Registrar el observer para cálculos automáticos de fechas y contraseña portal
        static::observe(ExpedienteObserver::class);

        static::creating(function (Expediente $exp) {
            if (empty($exp->folio)) {
                $año = now()->year;
                // Usar MAX del número secuencial para evitar duplicados con soft-deletes o concurrencia
                $prefijo = 'EXP-' . $año . '-';
                $ultimo  = static::withTrashed()
                    ->where('folio', 'like', $prefijo . '%')
                    ->orderByRaw('CAST(SUBSTRING(folio, ' . (strlen($prefijo) + 1) . ') AS UNSIGNED) DESC')
                    ->value('folio');

                $siguiente = $ultimo
                    ? (int) substr($ultimo, strlen($prefijo)) + 1
                    : 1;

                // Reintento en caso de colisión (race condition)
                $intentos = 0;
                do {
                    $folio = $prefijo . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
                    $existe = static::withTrashed()->where('folio', $folio)->exists();
                    if ($existe) {
                        $siguiente++;
                    }
                    $intentos++;
                } while ($existe && $intentos < 20);

                $exp->folio = $folio;
            }
            if (empty($exp->fecha_apertura)) {
                $exp->fecha_apertura = now()->toDateString();
            }
        });
    }

    // --- Relaciones ---

    public function tipoTramite(): BelongsTo
    {
        return $this->belongsTo(TipoTramite::class);
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(EtapaTramite::class, 'etapa_tramite_id');
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisor_id');
    }

    public function contacto(): BelongsTo
    {
        return $this->belongsTo(Contacto::class);
    }

    public function acreditadoRegistrado(): BelongsTo
    {
        return $this->belongsTo(Acreditado::class, 'acreditado_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoExpediente::class);
    }

    public function seguimientos(): HasMany
    {
        return $this->hasMany(SeguimientoExpediente::class)->latest();
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(GastoFinanciado::class);
    }

    public function comision(): HasMany
    {
        return $this->hasMany(Comision::class);
    }

    // --- Helpers ---

    public function getTotalACobrarAttribute(): float
    {
        return (float) $this->honorarios_monto + (float) $this->total_gastos_financiados;
    }
}
