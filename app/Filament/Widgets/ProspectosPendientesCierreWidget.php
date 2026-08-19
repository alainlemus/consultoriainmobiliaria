<?php

namespace App\Filament\Widgets;

use App\Models\Contacto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class ProspectosPendientesCierreWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    protected function getTableHeading(): string
    {
        $count = Contacto::where('estado_prospecto', 'pendiente_cierre')->count();
        return 'Prospectos pendientes de cierre' . ($count > 0 ? " ({$count})" : '');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Contacto::query()
                    ->with('asesor:id,name')
                    ->where('estado_prospecto', 'pendiente_cierre')
                    ->orderBy('fecha_envio_dueno', 'asc')
            )
            ->emptyStateHeading('Sin prospectos pendientes')
            ->emptyStateDescription('Cuando un asesor envíe un prospecto al gestor, aparecerá aquí.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->columns([
                Tables\Columns\TextColumn::make('fecha_envio_dueno')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->asesor?->name ?? '—'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Prospecto')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn ($record) => $record->telefono ?? '—'),

                Tables\Columns\TextColumn::make('servicio')
                    ->label('Servicio')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'infonavit'  => 'INFONAVIT',
                        'fovissste'  => 'FOVISSSTE',
                        'avaluo'     => 'Avalúo',
                        'escrituras' => 'Escrituración',
                        'asesoria'   => 'Asesoría',
                        default      => ucfirst($state ?? '—'),
                    })
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('modalidad_cierre')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'telefono'         => 'info',
                        'cita_oficina'     => 'warning',
                        'visita_domicilio' => 'danger',
                        'whatsapp'         => 'success',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'telefono'         => 'Teléfono',
                        'cita_oficina'     => 'Cita en oficina',
                        'visita_domicilio' => 'Visita domicilio',
                        'whatsapp'         => 'WhatsApp',
                        default            => '—',
                    }),

                Tables\Columns\TextColumn::make('monto_credito_estimado')
                    ->label('Monto estimado')
                    ->money('MXN')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('notas_cierre')
                    ->label('Notas del asesor')
                    ->limit(60)
                    ->placeholder('Sin notas')
                    ->wrap(),
            ])
            ->actions([
                \Filament\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Contacto $record) => \App\Filament\Resources\ContactoResource::getUrl('edit', ['record' => $record]))
                    ->color('gray'),
            ]);
    }
}
