<?php

namespace App\Http\Controllers;

use App\Exports\KpisExport;
use App\Models\Comision;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\GastoFinanciado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class KpisReporteController extends Controller
{
    /**
     * Descarga el reporte en Excel.
     * GET /admin/reportes/kpis/excel?anio=2025&mes=5
     */
    public function excel(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('super_admin'), 403);

        $anio = (int) ($request->get('anio', now()->year));
        $mes  = $request->filled('mes') ? (int) $request->get('mes') : null;

        $sufijo   = $mes ? Carbon::create($anio, $mes, 1)->format('Y-m') : $anio;
        $filename = "KPIs_{$sufijo}.xlsx";

        return Excel::download(new KpisExport($anio, $mes), $filename);
    }

    /**
     * Descarga el reporte en PDF.
     * GET /admin/reportes/kpis/pdf?anio=2025&mes=5
     */
    public function pdf(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasRole('super_admin'), 403);

        $anio = (int) ($request->get('anio', now()->year));
        $mes  = $request->filled('mes') ? (int) $request->get('mes') : null;

        $data = $this->buildData($anio, $mes);

        $sufijo   = $mes ? Carbon::create($anio, $mes, 1)->format('Y-m') : $anio;
        $filename = "KPIs_{$sufijo}.pdf";

        $pdf = Pdf::loadView('reports.kpis_pdf', $data)
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'dpi'             => 150,
                'defaultFont'     => 'DejaVu Sans',
                'isRemoteEnabled' => false,
            ]);

        return $pdf->download($filename);
    }

    // ─── Datos compartidos ────────────────────────────────────────────────────

    private function buildData(int $anio, ?int $mes): array
    {
        $periodo = $mes
            ? Carbon::create($anio, $mes, 1)->translatedFormat('F Y')
            : 'Año ' . $anio;

        $cerQ = fn () => Expediente::where('estado', 'cerrado')
            ->when($mes, fn ($q) => $q->whereMonth('fecha_cierre', $mes))
            ->whereYear('fecha_cierre', $anio);

        $comQ = fn () => Comision::query()
            ->when($mes, fn ($q) => $q->whereMonth('fecha_generacion', $mes))
            ->whereYear('fecha_generacion', $anio);

        $gastoQ = fn () => GastoFinanciado::query()
            ->when($mes, fn ($q) => $q->whereMonth('fecha_pago', $mes))
            ->whereYear('fecha_pago', $anio);

        $abiertos   = Expediente::query()
            ->when($mes, fn ($q) => $q->whereMonth('fecha_apertura', $mes))
            ->whereYear('fecha_apertura', $anio)->count();

        $cerrados   = $cerQ()->count();
        $cancelados = Expediente::where('estado', 'cancelado')
            ->when($mes, fn ($q) => $q->whereMonth('updated_at', $mes))
            ->whereYear('updated_at', $anio)->count();
        $activos    = Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])->count();

        $honTotal    = $cerQ()->sum('honorarios_monto');
        $honPagados  = $cerQ()->where('honorarios_pagados', true)->sum('honorarios_monto');
        $honPendientes = $honTotal - $honPagados;
        $ticket      = $cerrados > 0 ? round($honTotal / $cerrados, 2) : 0;
        $tasaCierre  = $abiertos > 0 ? round(($cerrados / $abiertos) * 100, 1) : 0;

        $comGen      = $comQ()->sum('monto_comision');
        $comPag      = $comQ()->where('estado', 'pagada')->sum('monto_comision');
        $comPend     = $comQ()->where('estado', 'pendiente')->sum('monto_comision');

        $gastosPagados  = $gastoQ()->sum('monto');

        $ingresosCobrados  = $honPagados;
        $egresoTotal       = $comPag + $gastosPagados;
        $flujoNeto         = $ingresosCobrados - $egresoTotal;
        $margen            = $ingresosCobrados > 0
            ? round(($flujoNeto / $ingresosCobrados) * 100, 1) : 0;

        $prospectos   = Contacto::when($mes, fn ($q) => $q->whereMonth('created_at', $mes))
            ->whereYear('created_at', $anio)->count();
        $prospNuevos  = Contacto::where('estado_prospecto', 'nuevo')->count();

        // Expedientes para la tabla de detalle
        $expedientes = Expediente::with(['tipoTramite', 'etapa', 'asesor'])
            ->when($mes, fn ($q) => $q->whereMonth('fecha_apertura', $mes))
            ->whereYear('fecha_apertura', $anio)
            ->orderBy('fecha_apertura')
            ->get();

        // Comisiones para la tabla de detalle
        $comisiones = Comision::with(['expediente', 'asesor'])
            ->when($mes, fn ($q) => $q->whereMonth('fecha_generacion', $mes))
            ->whereYear('fecha_generacion', $anio)
            ->orderBy('fecha_generacion')
            ->get();

        // Resumen mensual (solo si es reporte anual)
        $mensual = collect();
        if (!$mes) {
            $nombMeses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            for ($m = 1; $m <= 12; $m++) {
                $mensual->push([
                    'mes'        => $nombMeses[$m - 1],
                    'abiertos'   => Expediente::whereYear('fecha_apertura', $anio)->whereMonth('fecha_apertura', $m)->count(),
                    'cerrados'   => Expediente::where('estado','cerrado')->whereYear('fecha_cierre', $anio)->whereMonth('fecha_cierre', $m)->count(),
                    'cancelados' => Expediente::where('estado','cancelado')->whereYear('updated_at', $anio)->whereMonth('updated_at', $m)->count(),
                    'honorarios' => Expediente::where('estado','cerrado')->where('honorarios_pagados',true)->whereYear('fecha_cierre',$anio)->whereMonth('fecha_cierre',$m)->sum('honorarios_monto'),
                    'comisiones' => Comision::where('estado','pagada')->whereYear('fecha_pago',$anio)->whereMonth('fecha_pago',$m)->sum('monto_comision'),
                    'gastos'     => GastoFinanciado::whereYear('fecha_pago',$anio)->whereMonth('fecha_pago',$m)->whereNotNull('fecha_pago')->sum('monto'),
                ]);
            }
        }

        return compact(
            'periodo', 'anio', 'mes',
            'abiertos', 'cerrados', 'cancelados', 'activos',
            'tasaCierre', 'ticket',
            'honTotal', 'honPagados', 'honPendientes',
            'comGen', 'comPag', 'comPend',
            'gastosPagados',
            'ingresosCobrados', 'egresoTotal', 'flujoNeto', 'margen',
            'prospectos', 'prospNuevos',
            'expedientes', 'comisiones', 'mensual'
        );
    }
}
