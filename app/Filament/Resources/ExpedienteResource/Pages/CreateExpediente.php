<?php

namespace App\Filament\Resources\ExpedienteResource\Pages;

use App\Filament\Resources\ExpedienteResource;
use App\Models\User;
use App\Notifications\NuevoExpedienteCreado;
use Filament\Resources\Pages\CreateRecord;

class CreateExpediente extends CreateRecord
{
    protected static string $resource = ExpedienteResource::class;

    protected function afterCreate(): void
    {
        // Notificar a todos los usuarios con rol super_admin
        $admins = User::role('super_admin')->where('activo', true)->get();

        foreach ($admins as $admin) {
            $admin->notify(new NuevoExpedienteCreado($this->record));
        }
    }
}
