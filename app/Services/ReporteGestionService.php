<?php

namespace App\Services;

use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Reúne los datos de los reportes de gestión (diario, semanal, mensual):
 * prospectos registrados y documentos subidos desde la app.
 */
class ReporteGestionService
{
    /**
     * @param  Carbon  $desde  Inicio del período (inclusive)
     * @param  Carbon  $hasta  Fin del período (inclusive, fin del día)
     * @param  string  $tipo   'diario' | 'semanal' | 'mensual'
     */
    public function generar(Carbon $desde, Carbon $hasta, string $tipo): array
    {
        return [
            'tipo'        => $tipo,
            'desde'       => $desde,
            'hasta'       => $hasta,
            'generado_en' => now(),
            'prospectos'  => $this->prospectos($desde, $hasta),
            'expedientes' => $this->expedientes($desde, $hasta),
            'documentos'  => $this->documentos($desde, $hasta),
            'por_asesor'  => $this->actividadPorAsesor($desde, $hasta),
        ];
    }

    private function prospectos(Carbon $desde, Carbon $hasta): array
    {
        $nuevos = Contacto::with('asesor:id,name')
            ->whereBetween('created_at', [$desde, $hasta])
            ->latest()
            ->get();

        return [
            'nuevos_periodo' => $nuevos->count(),
            'activos_total'  => Contacto::whereNotIn('estado_prospecto', ['descartado', 'convertido'])->whereDoesntHave('expedientes')->count(),
            'lista_nuevos'   => $nuevos->map(fn ($c) => [
                'nombre' => $c->nombre,
                'asesor' => $c->asesor?->name ?? '—',
            ])->values()->toArray(),
        ];
    }

    private function expedientes(Carbon $desde, Carbon $hasta): array
    {
        return [
            'abiertos_periodo' => Expediente::whereBetween('created_at', [$desde, $hasta])->count(),
            'total'            => Expediente::count(),
        ];
    }

    private function documentos(Carbon $desde, Carbon $hasta): array
    {
        $conArchivo = DocumentoExpediente::whereNotNull('ruta_archivo');

        return [
            'subidos_periodo' => (clone $conArchivo)->whereBetween('created_at', [$desde, $hasta])->count(),
            'por_capturar'    => (clone $conArchivo)->whereNull('capturado_at')->count(),
        ];
    }

    private function actividadPorAsesor(Carbon $desde, Carbon $hasta): array
    {
        return User::role('asesor')->where('activo', true)->get()->map(function (User $asesor) use ($desde, $hasta) {
            return [
                'asesor'      => $asesor->name,
                'prospectos'  => $asesor->contactos()->whereBetween('created_at', [$desde, $hasta])->count(),
                'expedientes' => $asesor->expedientes()->whereBetween('created_at', [$desde, $hasta])->count(),
                'documentos'  => $asesor->documentos()
                    ->whereNotNull('documentos_expediente.ruta_archivo')
                    ->whereBetween('documentos_expediente.created_at', [$desde, $hasta])
                    ->count(),
            ];
        })->values()->toArray();
    }
}
