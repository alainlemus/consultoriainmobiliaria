<?php

namespace App\Exports;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\Expediente;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class KpisExport implements WithMultipleSheets
{
    public function __construct(
        public int $anio,
        public ?int $mes = null,
    ) {}

    public function sheets(): array
    {
        return [
            new KpisResumenSheet($this->anio, $this->mes),
            new KpisExpedientesSheet($this->anio, $this->mes),
            new KpisComisionesSheet($this->anio, $this->mes),
            new KpisMensualSheet($this->anio),
        ];
    }
}

// ─── Hoja 1: Resumen KPIs ─────────────────────────────────────────────────────
class KpisResumenSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(public int $anio, public ?int $mes) {}

    public function title(): string { return 'Resumen KPIs'; }

    public function headings(): array
    {
        return ['Indicador', 'Valor', 'Periodo'];
    }

    public function collection()
    {
        $mes  = $this->mes;
        $anio = $this->anio;
        $periodo = $mes
            ? Carbon::create($anio, $mes, 1)->translatedFormat('F Y')
            : 'Año ' . $anio;

        $expQ = fn () => Expediente::query()
            ->when($mes, fn ($q) => $q->whereMonth('fecha_apertura', $mes))
            ->whereYear('fecha_apertura', $anio);

        $cerQ = fn () => Expediente::where('estado', 'cerrado')
            ->when($mes, fn ($q) => $q->whereMonth('fecha_cierre', $mes))
            ->whereYear('fecha_cierre', $anio);

        $comQ = fn () => Comision::query()
            ->when($mes, fn ($q) => $q->whereMonth('fecha_generacion', $mes))
            ->whereYear('fecha_generacion', $anio);

        $abiertos   = $expQ()->count();
        $cerrados   = $cerQ()->count();
        $cancelados = Expediente::where('estado', 'cancelado')
            ->when($mes, fn ($q) => $q->whereMonth('updated_at', $mes))
            ->whereYear('updated_at', $anio)->count();
        $activos    = Expediente::whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])->count();

        $honTotal   = $cerQ()->sum('honorarios_monto');
        $honPagados = $cerQ()->where('honorarios_pagados', true)->sum('honorarios_monto');
        $ticket     = $cerrados > 0 ? round($honTotal / $cerrados, 2) : 0;
        $tasa       = $abiertos > 0 ? round(($cerrados / $abiertos) * 100, 1) : 0;

        $comGen  = $comQ()->sum('monto_comision');
        $comPag  = $comQ()->where('estado', 'pagada')->sum('monto_comision');
        $comPend = $comQ()->where('estado', 'pendiente')->sum('monto_comision');

        $prospectos = Contacto::when($mes, fn ($q) => $q->whereMonth('created_at', $mes))
            ->whereYear('created_at', $anio)->count();
        $prospNuevos = Contacto::where('estado_prospecto', 'nuevo')
            ->when($mes, fn ($q) => $q->whereMonth('created_at', $mes))
            ->whereYear('created_at', $anio)->count();

        return collect([
            ['Expedientes Abiertos',          $abiertos,                            $periodo],
            ['Expedientes Cerrados',           $cerrados,                            $periodo],
            ['Expedientes Cancelados',         $cancelados,                          $periodo],
            ['Expedientes Activos Hoy',        $activos,                             'Actual'],
            ['Tasa de Cierre',                 $tasa . '%',                          $periodo],
            ['Honorarios Totales (MXN)',        number_format($honTotal, 2, '.', ','),    $periodo],
            ['Honorarios Pagados (MXN)',        number_format($honPagados, 2, '.', ','),  $periodo],
            ['Honorarios Pendientes (MXN)',     number_format($honTotal - $honPagados, 2, '.', ','), $periodo],
            ['Ticket Promedio (MXN)',           number_format($ticket, 2, '.', ','),      $periodo],
            ['Comisiones Generadas (MXN)',      number_format($comGen, 2, '.', ','),      $periodo],
            ['Comisiones Pagadas (MXN)',        number_format($comPag, 2, '.', ','),      $periodo],
            ['Comisiones Pendientes (MXN)',     number_format($comPend, 2, '.', ','),     $periodo],
            ['Prospectos Captados',             $prospectos,                          $periodo],
            ['Prospectos Sin Atender',          $prospNuevos,                         'Actual'],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Header row style
                $sheet->getStyle('A1:C1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                ]);
                $sheet->getStyle('A1:C100')->getAlignment()->setWrapText(true);
            },
        ];
    }
}

// ─── Hoja 2: Expedientes detalle ──────────────────────────────────────────────
class KpisExpedientesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(public int $anio, public ?int $mes) {}

    public function title(): string { return 'Expedientes'; }

    public function headings(): array
    {
        return ['Folio', 'Acreditado', 'Tipo Trámite', 'Etapa', 'Estado', 'Asesor',
                'Honorarios', 'Pagados', 'Fecha Apertura', 'Fecha Cierre'];
    }

    public function collection()
    {
        return Expediente::with(['tipoTramite', 'etapa', 'asesor'])
            ->when($this->mes, fn ($q) => $q->whereMonth('fecha_apertura', $this->mes))
            ->whereYear('fecha_apertura', $this->anio)
            ->orderBy('fecha_apertura')
            ->get()
            ->map(fn ($e) => [
                $e->folio,
                $e->acreditado_nombre,
                $e->tipoTramite?->nombre,
                $e->etapa?->nombre,
                $e->estado,
                $e->asesor?->name,
                number_format($e->honorarios_monto, 2, '.', ','),
                $e->honorarios_pagados ? 'Sí' : 'No',
                $e->fecha_apertura?->format('d/m/Y'),
                $e->fecha_cierre?->format('d/m/Y') ?? '—',
            ]);
    }
}

// ─── Hoja 3: Comisiones detalle ───────────────────────────────────────────────
class KpisComisionesSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(public int $anio, public ?int $mes) {}

    public function title(): string { return 'Comisiones'; }

    public function headings(): array
    {
        return ['Expediente', 'Asesor', 'Monto Base', '% Comisión', 'Monto Comisión',
                'Estado', 'Fecha Generación', 'Fecha Pago'];
    }

    public function collection()
    {
        return Comision::with(['expediente', 'asesor'])
            ->when($this->mes, fn ($q) => $q->whereMonth('fecha_generacion', $this->mes))
            ->whereYear('fecha_generacion', $this->anio)
            ->orderBy('fecha_generacion')
            ->get()
            ->map(fn ($c) => [
                $c->expediente?->folio,
                $c->asesor?->name,
                number_format($c->monto_base, 2, '.', ','),
                $c->porcentaje_comision . '%',
                number_format($c->monto_comision, 2, '.', ','),
                ucfirst($c->estado),
                $c->fecha_generacion?->format('d/m/Y'),
                $c->fecha_pago?->format('d/m/Y') ?? '—',
            ]);
    }
}

// ─── Hoja 4: Resumen mensual del año ─────────────────────────────────────────
class KpisMensualSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(public int $anio) {}

    public function title(): string { return 'Por Mes'; }

    public function headings(): array
    {
        return ['Mes', 'Abiertos', 'Cerrados', 'Cancelados', 'Honorarios (MXN)', 'Comisiones (MXN)'];
    }

    public function collection()
    {
        $meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $rows = collect();

        for ($m = 1; $m <= 12; $m++) {
            $abiertos   = Expediente::whereYear('fecha_apertura', $this->anio)->whereMonth('fecha_apertura', $m)->count();
            $cerrados   = Expediente::where('estado','cerrado')->whereYear('fecha_cierre', $this->anio)->whereMonth('fecha_cierre', $m)->count();
            $cancelados = Expediente::where('estado','cancelado')->whereYear('updated_at', $this->anio)->whereMonth('updated_at', $m)->count();
            $honorarios = Expediente::where('estado','cerrado')->whereYear('fecha_cierre', $this->anio)->whereMonth('fecha_cierre', $m)->sum('honorarios_monto');
            $comisiones = Comision::where('estado','pagada')->whereYear('fecha_pago', $this->anio)->whereMonth('fecha_pago', $m)->sum('monto_comision');

            $rows->push([
                $meses[$m - 1],
                $abiertos,
                $cerrados,
                $cancelados,
                number_format($honorarios, 2, '.', ','),
                number_format($comisiones, 2, '.', ','),
            ]);
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
                ]);
            },
        ];
    }
}
