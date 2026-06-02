<?php

namespace App\Filament\Resources\ContactoResource\Pages;

use App\Filament\Resources\ContactoResource;
use App\Filament\Resources\ExpedienteResource;
use App\Models\Expediente;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditContacto extends EditRecord
{
    protected static string $resource = ContactoResource::class;

    protected function beforeEdit(): void
    {
        $record = $this->getRecord();

        if (Auth::user()->hasRole('asesor') &&
            in_array($record->estado_prospecto, ['pendiente_cierre', 'contrato_firmado', 'convertido'])) {
            $this->redirect(ContactoResource::getUrl('view', ['record' => $record]));
        }

        if ($record->estado_prospecto === 'convertido') {
            $this->redirect(ContactoResource::getUrl('view', ['record' => $record]));
        }
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $isAsesor = Auth::user()->hasRole('asesor');
        $isLocked = in_array($record->estado_prospecto, ['pendiente_cierre', 'contrato_firmado', 'convertido']);

        if ($isAsesor && $isLocked) {
            return [];
        }

        $actions = [];

        // Botones de descarga de contratos (solo si hay expediente linked y solo super_admin)
        if (Auth::user()->hasRole('super_admin')) {
            $expediente = Expediente::where('contacto_id', $record->id)->first();
            if ($expediente) {
                $actions[] = Actions\Action::make('descargar_contrato')
                    ->label('Descargar Contrato')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(url('/admin/contratos/' . $expediente->id . '/prestacion-servicios'))
                    ->openUrlInNewTab();

                $actions[] = Actions\Action::make('descargar_convenio')
                    ->label('Descargar Convenio')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->url(url('/admin/contratos/' . $expediente->id . '/convenio-honorarios'))
                    ->openUrlInNewTab();
            }
        }

if (Auth::user()->hasRole('super_admin') && $record->estado_prospecto === 'pendiente_cierre') {
            $actions[] = Actions\Action::make('iniciar_expediente')
                ->label('Iniciar Expediente')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('¿Iniciar expediente con este prospecto?')
                ->modalDescription('Se creará un nuevo expediente vinculado a este contacto.')
                ->action(function () use ($record) {
                    $expediente = Expediente::create([
                        'contacto_id'        => $record->id,
                        'asesor_id'          => $record->asesor_id ?? Auth::id(),
                        'acreditado_nombre'  => $record->nombre,
                        'acreditado_telefono'=> $record->telefono,
                        'acreditado_email'   => $record->email,
                        'acreditado_curp'    => $record->curp,
                        'tipo_tramite_id'    => 1,
                        'etapa_tramite_id'   => 1,
                        'estado'             => 'en_proceso',
                    ]);
                    $record->update(['estado_prospecto' => 'convertido']);
                    $this->redirect(ExpedienteResource::getUrl('edit', ['record' => $expediente]), navigate: true);
                });
        }

        $actions[] = Actions\DeleteAction::make();

        return $actions;
    }
}
