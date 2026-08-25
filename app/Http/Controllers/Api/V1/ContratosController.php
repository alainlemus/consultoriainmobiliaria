<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cobertura;
use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * PUT /api/v1/contratos/prestacion-servicios/config
     *
     * Guarda el texto de la plantilla — solo super_admin (validado aquí,
     * no solo del lado de la app). Mismo respaldo que el panel de Filament
     * (App\Filament\Pages\ContratosSettings): tabla `configuraciones` para
     * el texto, y `Cobertura` (primer registro) para el domicilio del
     * prestador, que no vive en `configuraciones`.
     */
    public function updatePrestacionServiciosConfig(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403, 'Solo el administrador puede editar la plantilla del contrato.');

        $data = $request->validate([
            'site_name'                          => ['required', 'string', 'max:150'],
            'firma_prestador'                     => ['required', 'string', 'max:150'],
            'firma_juridico'                       => ['required', 'string', 'max:150'],
            'domicilio_prestador'                  => ['required', 'string', 'max:255'],
            'contrato_intro'                       => ['nullable', 'string'],
            'contrato_declaraciones_prestador'     => ['nullable', 'string'],
            'contrato_declaraciones_interesado'    => ['nullable', 'string'],
            'contrato_clausulas'                   => ['nullable', 'string'],
        ]);

        foreach ([
            'site_name', 'firma_prestador', 'firma_juridico',
            'contrato_intro', 'contrato_declaraciones_prestador',
            'contrato_declaraciones_interesado', 'contrato_clausulas',
        ] as $clave) {
            Configuracion::set($clave, $data[$clave] ?? '');
        }

        if (Cobertura::exists()) {
            Cobertura::first()->update(['detalle' => $data['domicilio_prestador']]);
        }

        return $this->prestacionServiciosConfig();
    }
}
