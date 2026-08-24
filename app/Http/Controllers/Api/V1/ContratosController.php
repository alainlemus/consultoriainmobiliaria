<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cobertura;
use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;

class ContratosController extends Controller
{
    /**
     * GET /api/v1/contratos/prestacion-servicios/config
     *
     * Texto configurable del Contrato de Prestación de Servicios
     * (el mismo que usa resources/views/contratos/prestacion_servicios.blade.php
     * vía Filament > ContratosSettings). La app lo cachea localmente al
     * sincronizar para poder generar el PDF sin conexión.
     */
    public function prestacionServiciosConfig(): JsonResponse
    {
        return response()->json([
            'data' => [
                'site_name'                         => Configuracion::get('site_name') ?? 'Consultoría Inmobiliaria',
                'firma_prestador'                    => Configuracion::get('firma_prestador', 'C. JOSE ANTONIO SOLIS SANTUARIO'),
                'firma_juridico'                      => Configuracion::get('firma_juridico', 'LIC. LUZ ANGÉLICA PÉREZ MEJÍA'),
                'contrato_intro'                      => Configuracion::get('contrato_intro', ''),
                'contrato_declaraciones_prestador'    => Configuracion::get('contrato_declaraciones_prestador', ''),
                'contrato_declaraciones_interesado'   => Configuracion::get('contrato_declaraciones_interesado', ''),
                'contrato_clausulas'                  => Configuracion::get('contrato_clausulas', ''),
                'domicilio_prestador'                 => Cobertura::first()?->detalle ?: 'Huejutla de Reyes, Hidalgo',
            ],
        ]);
    }
}
