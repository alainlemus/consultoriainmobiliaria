<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoExpediente extends Model
{
    protected $table = 'documentos_expediente';

    protected $fillable = [
        'expediente_id',
        'tipo',
        'nombre',
        'estado',
        'notas',
        'ruta_archivo',
    ];

    // Catálogo de documentos por tipo de trámite + tipo de inmueble
    // vivienda_tipo: 'casa' | 'terreno' | null (sin inmueble)
    public static function catalogoPara(int $tipoTramiteId, ?string $viviendaTipo): array
    {
        // Documentos que siempre aplican al acreditado
        $siempre = [
            ['tipo' => 'ine',                  'nombre' => 'INE con QR (acreditado)'],
            ['tipo' => 'curp',                 'nombre' => 'CURP con QR'],
            ['tipo' => 'comprobante_domicilio', 'nombre' => 'Comprobante de domicilio'],
            ['tipo' => 'sit_fiscal',           'nombre' => 'Constancia de situación fiscal'],
            ['tipo' => 'talones_pago',         'nombre' => 'Últimos 3 talones de pago'],
            ['tipo' => 'formatos_sofom',       'nombre' => 'Formatos SOFOM'],
        ];

        // Documentos del inmueble (cuando hay compra)
        $conInmueble = [1, 2, 3, 5]; // FOVISSSTE, INFONAVIT, Combo, Escrituras
        $docsInmueble = [];

        if (in_array($tipoTramiteId, $conInmueble) && $viviendaTipo) {
            $docsInmueble[] = ['tipo' => 'escritura', 'nombre' => 'Escritura del inmueble'];
            $docsInmueble[] = ['tipo' => 'predial',   'nombre' => 'Boleta predial'];

            if ($viviendaTipo === 'casa') {
                $docsInmueble[] = ['tipo' => 'recibo_agua', 'nombre' => 'Recibo de agua'];
                $docsInmueble[] = ['tipo' => 'recibo_luz',  'nombre' => 'Recibo de luz'];
            }
        }

        return array_merge($siempre, $docsInmueble);
    }

    public static function estadoLabel(string $estado): string
    {
        return match($estado) {
            'recibido'  => 'Recibido',
            'no_aplica' => 'No aplica',
            default     => 'Pendiente',
        };
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class);
    }
}
