<?php

namespace App\Filament\Resources\UbicacionResource\Pages;

use App\Filament\Resources\UbicacionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewUbicacion extends ViewRecord
{
    protected static string $resource = UbicacionResource::class;

    protected string $view = 'filament.resources.ubicacion.view-ubicacion';

    public function getTitle(): string
    {
        $tipo = $this->record->tipo === 'visita_cliente' ? 'Visita cliente' : 'Propiedad';
        return "{$tipo} — " . ($this->record->visitado_en?->format('d/m/Y H:i') ?? '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('← Volver')
                ->url(UbicacionResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    /**
     * Fotos con URLs firmadas para el blade. Expiración redondeada a la hora
     * para que la misma foto tenga la misma URL en renders repetidos dentro
     * de la misma hora y el navegador pueda reutilizar la copia cacheada.
     */
    public function getFotosConUrl(): \Illuminate\Support\Collection
    {
        $expira = now()->addHour()->startOfHour();

        return $this->record->fotos->map(fn ($f) => [
            'id'  => $f->id,
            'url' => \URL::signedRoute('api.ubicacion.foto', ['fotoId' => $f->id], $expira),
        ]);
    }
}
