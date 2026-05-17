<?php

namespace App\Models;

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
        // Vendedor
        'vendedor_nombre', 'vendedor_curp', 'vendedor_rfc', 'vendedor_telefono', 'vendedor_email',
        'vendedor_domicilio', 'vendedor_requiere_acta_matrimonio', 'vendedor_banco', 'vendedor_clabe',
        // Vivienda
        'vivienda_calle', 'vivienda_numero', 'vivienda_colonia', 'vivienda_municipio',
        'vivienda_estado', 'vivienda_cp', 'vivienda_tipo', 'vivienda_descripcion_titulo',
        // Trámite
        'uso_credito', 'modalidad_credito', 'banco_participante',
        'monto_credito', 'subcuenta_vivienda', 'monto_total_estimado',
        'honorarios_porcentaje', 'honorarios_monto', 'honorarios_pagados', 'fecha_pago_honorarios',
        'total_gastos_financiados', 'notas_internas', 'fecha_apertura', 'fecha_cierre',
        // Cónyuge (crédito conyugal / mancomunado)
        'conyuge_nombre', 'conyuge_curp', 'conyuge_rfc', 'conyuge_telefono',
        'conyuge_institucion', 'conyuge_numero_credito',
        // Pensionado
        'numero_pension', 'clave_pension', 'fecha_inicio_pension', 'monto_pension_mensual',
    ];

    protected $casts = [
        'acreditado_personas_autorizadas' => 'array',
        'acreditado_referencias'          => 'array',
        'acreditado_fecha_nacimiento'     => 'date',
        'honorarios_pagados'              => 'boolean',
        'vendedor_requiere_acta_matrimonio' => 'boolean',
        'fecha_pago_honorarios'           => 'date',
        'fecha_apertura'                  => 'date',
        'fecha_cierre'                    => 'date',
        'fecha_inicio_pension'            => 'date',
        'monto_credito'                   => 'decimal:2',
        'subcuenta_vivienda'              => 'decimal:2',
        'monto_total_estimado'            => 'decimal:2',
        'honorarios_porcentaje'           => 'decimal:2',
        'honorarios_monto'                => 'decimal:2',
        'total_gastos_financiados'        => 'decimal:2',
        'monto_pension_mensual'           => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Expediente $exp) {
            if (empty($exp->folio)) {
                $año  = now()->year;
                $last = static::whereYear('created_at', $año)->count() + 1;
                $exp->folio = 'EXP-' . $año . '-' . str_pad($last, 4, '0', STR_PAD_LEFT);
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
