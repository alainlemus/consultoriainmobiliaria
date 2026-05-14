<?php

namespace App\Filament\Resources\ContactoResource\Pages;

use App\Filament\Resources\ContactoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateContacto extends CreateRecord
{
    protected static string $resource = ContactoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si el usuario es asesor, se asigna automáticamente como responsable
        if (Auth::user()?->hasRole('asesor')) {
            $data['asesor_id'] = Auth::id();
        }

        return $data;
    }
}
