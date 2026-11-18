<?php

namespace App\Filament\Resources\VehiculoReservaResource\Pages;

use App\Filament\Resources\VehiculoReservaResource;
use App\Models\Vehiculo;
use App\Models\VehiculoCarga;
use App\Models\VehiculoMovimiento;
use App\Models\VehiculoReserva;
use App\Services\VehiculoService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;

/** Detalle de una reserva: datos, salida y entrega lado a lado con sus fotos, llaves y el recorrido GPS. */
class ViewVehiculoReserva extends ViewRecord
{
    protected static string $resource = VehiculoReservaResource::class;

    protected string $view = 'filament.resources.vehiculo-reserva.view';

    public function getTitle(): string
    {
        return "Reserva #{$this->record->id} · {$this->record->vehiculo->etiqueta()}";
    }

    protected function getHeaderActions(): array
    {
        return [
            ...VehiculoReservaResource::acciones(),
            Action::make('volver')->label('← Volver')->url(VehiculoReservaResource::getUrl('index'))->color('gray'),
        ];
    }

    public function datos(): array
    {
        /** @var VehiculoReserva $r */
        $r = $this->record->fresh(['vehiculo.tipo', 'asesor', 'decididoPor', 'cita', 'contacto', 'expediente', 'salida.fotos', 'salida.llavesDe', 'entrega.fotos', 'movimientos.fotos', 'movimientos.user', 'movimientos.llavesDe', 'cargas.movimiento.fotos', 'desplazadaPor']);
        $svc = app(VehiculoService::class);
        $etiquetas = collect($r->vehiculo->tipo->fotosObligatorias())->pluck('etiqueta', 'clave')->all() + VehiculoService::ETIQUETAS_EXTRA;

        $mov = fn (?VehiculoMovimiento $m) => $m ? [
            'cuando' => $m->created_at->setTimezone(VehiculoService::TZ)->format('d/m/Y g:i a'),
            'km' => $m->km,
            'gasolina' => $m->gasolina !== null ? Vehiculo::GASOLINA[$m->gasolina] : '—',
            'notas_danos' => $m->notas_danos,
            'checklist' => $m->checklist ?? [],
            'llaves' => match ($m->llaves_modo) {
                'oficina' => $m->tipo === VehiculoMovimiento::SALIDA ? 'Las tomó de la oficina' : 'Las dejó en la oficina',
                'codigo' => 'Se las dio '.($m->llavesDe?->name ?? '—').' (código)',
                'ya_las_tenia' => $m->tipo === VehiculoMovimiento::SALIDA ? 'Ya las tenía' : 'Se quedó con las llaves',
                default => '—',
            },
            'ubicacion' => $m->lat && $m->lng ? [$m->lat, $m->lng] : null,
            'fotos' => $m->fotos->map(fn ($f) => ['etiqueta' => $etiquetas[$f->clave] ?? ucfirst($f->clave), 'url' => $f->url(), 'thumb' => $f->url(true)])->all(),
        ] : null;

        $llaves = $r->movimientos->where('tipo', VehiculoMovimiento::LLAVES)->map(fn ($m) => [
            'cuando' => $m->created_at->setTimezone(VehiculoService::TZ)->format('d/m/Y g:i a'),
            'texto' => $m->llaves_destino === 'oficina'
                ? "{$m->user->name} dejó las llaves en la oficina"
                : "{$m->user->name} recibió las llaves de ".($m->llavesDe?->name ?? '—'),
        ])->values()->all();

        $eventos = $r->movimientos->whereIn('tipo', [VehiculoMovimiento::RESGUARDO, VehiculoMovimiento::RETOMAR])->sortBy('id')->map(fn ($m) => [
            'titulo' => $m->tipo === VehiculoMovimiento::RESGUARDO ? '🏠 Guardado en casa' : '☀️ Lo retomó',
            'cuando' => $m->created_at->setTimezone(VehiculoService::TZ)->format('d/m/Y g:i a'),
            'km' => $m->km,
            'gasolina' => $m->gasolina !== null ? Vehiculo::GASOLINA[$m->gasolina] : null,
            'ubicacion' => $m->lat && $m->lng ? "https://www.google.com/maps?q={$m->lat},{$m->lng}" : null,
            'fotos' => $m->fotos->map(fn ($f) => ['etiqueta' => $etiquetas[$f->clave] ?? ucfirst($f->clave), 'url' => $f->url(), 'thumb' => $f->url(true)])->all(),
        ])->values()->all();

        $cargas = $r->cargas->map(fn ($c) => [
            'cuando' => $c->created_at->setTimezone(VehiculoService::TZ)->format('d/m/Y g:i a'),
            'texto' => '$'.number_format((float) $c->monto, 2).' · '.rtrim(rtrim(number_format((float) $c->litros, 2), '0'), '.').' L · '.number_format($c->km).' km',
            'pago' => VehiculoCarga::PAGOS[$c->pago] ?? $c->pago,
            'reembolso' => $c->reembolsoEstado(),
            'fotos' => $c->movimiento->fotos->map(fn ($f) => ['etiqueta' => $etiquetas[$f->clave] ?? ucfirst($f->clave), 'url' => $f->url(), 'thumb' => $f->url(true)])->all(),
        ])->values()->all();

        $asesor = $r->asesor;

        return [
            'r' => $r,
            'cuando' => $svc->cuando($r),
            'estado' => VehiculoReserva::ESTADOS[$r->estado] ?? $r->estado,
            'color' => VehiculoReservaResource::color($r->estado),
            'fila' => $svc->posicionFila($r),
            'licencia' => [
                'vigente' => $asesor->licenciaVigente(),
                'vence' => $asesor->licencia_vence?->format('d/m/Y'),
                'url' => $asesor->licencia_foto ? URL::signedRoute('api.user.licencia', ['userId' => $asesor->id], now()->addHour()->startOfHour()) : null,
            ],
            'salida' => $mov($r->salida),
            'entrega' => $mov($r->entrega),
            'km' => ($r->salida && $r->entrega) ? max(0, $r->entrega->km - $r->salida->km) : null,
            'recorrido' => $svc->recorrido($r),
            'llaves' => $llaves,
            'eventos' => $eventos,
            'cargas' => $cargas,
            'alertas' => $r->alertas ?? [],
            'desplazada' => $r->desplazada_at ? [
                'motivo' => $r->desplazada_motivo,
                'por' => $r->desplazadaPor?->name,
                'cuando' => $r->desplazada_at->setTimezone(VehiculoService::TZ)->format('d/m/Y g:i a'),
            ] : null,
        ];
    }
}
