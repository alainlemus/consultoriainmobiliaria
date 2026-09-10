<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContratoGenerado extends Model
{
    protected $table = 'contratos_generados';

    protected $fillable = [
        'asesor_id', 'local_id', 'folio', 'tipo_tramite', 'ciudad',
        'acreditado_nombre', 'acreditado_curp', 'acreditado_rfc', 'acreditado_nss', 'acreditado_clave_elector', 'acreditado_domicilio',
        'solidario_nombre', 'solidario_curp', 'solidario_rfc', 'solidario_domicilio',
        'monto_credito', 'honorarios_porcentaje', 'honorarios_monto',
        'pdf_path', 'ine_acreditado_path', 'ine_solidario_path',
    ];

    protected $casts = [
        'monto_credito'         => 'decimal:2',
        'honorarios_porcentaje' => 'decimal:2',
        'honorarios_monto'      => 'decimal:2',
    ];

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /** Al borrar el registro, limpiar también los archivos del disco. */
    protected static function booted(): void
    {
        static::deleting(function (ContratoGenerado $contrato) {
            foreach ([$contrato->pdf_path, $contrato->ine_acreditado_path, $contrato->ine_solidario_path] as $path) {
                if ($path) {
                    Storage::disk('local')->delete($path);
                }
            }
        });
    }
}
