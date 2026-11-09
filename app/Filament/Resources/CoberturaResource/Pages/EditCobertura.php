<?php

namespace App\Filament\Resources\CoberturaResource\Pages;

use App\Filament\Resources\CoberturaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCobertura extends EditRecord
{
    protected static string $resource = CoberturaResource::class;
    public function getTitle(): string { return 'Editar zona: ' . $this->record->nombre; }
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('Eliminar zona')];
    }
    /** Oficinas creadas antes de las citas: mostrar el horario por defecto en vez de campos vacíos. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $h = array_merge(\App\Models\Cobertura::HORARIO_DEFAULT, array_filter((array) ($data['horario_citas'] ?? []), fn ($v) => $v !== null && $v !== '' && $v !== []));
        $h['dias'] = \App\Models\Cobertura::diasClave((array) $h['dias']);
        $data['horario_citas'] = $h;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
