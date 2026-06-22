<?php

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Filament\Resources\ExpedienteResource;
use App\Models\EtapaTramite;
use Filament\Resources\Pages\CreateRecord;

class CreateExpediente extends CreateRecord
{
    protected static string $resource = ExpedienteResource::class;

    // La notificación al super_admin se envía via ExpedienteObserver@created
    // para que cubra también la creación desde la API móvil.

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Garantizar que siempre haya etapa_tramite_id al crear.
        // Si no viene del formulario (asesor, capturista) o viene vacío,
        // asignar automáticamente la primera etapa del tipo de trámite.
        if (empty($data['etapa_tramite_id']) && ! empty($data['tipo_tramite_id'])) {
            $primeraEtapa = EtapaTramite::where('tipo_tramite_id', $data['tipo_tramite_id'])
                ->orderBy('orden')
                ->first();

            if ($primeraEtapa) {
                $data['etapa_tramite_id'] = $primeraEtapa->id;
            }
        }

        return $data;
    }
}
