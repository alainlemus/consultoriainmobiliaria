<?php

namespace App\Filament\Widgets;

use App\Models\Contacto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class AsesorProspectosWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Mis prospectos')
            ->description('Tus prospectos activos ordenados por actividad reciente.')
            ->query(
                Contacto::query()
                    ->where('asesor_id', Auth::id())
                    ->orderByDesc('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('servicio')
                    ->label('Servicio')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('estado_prospecto')
                    ->label('Estado')
                    ->colors([
                        'gray'    => 'nuevo',
                        'primary' => 'contactado',
                        'info'    => 'en_seguimiento',
                        'warning' => 'pendiente_cierre',
                        'success' => 'convertido',
                        'danger'  => 'descartado',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'nuevo'            => 'Nuevo',
                        'contactado'       => 'Contactado',
                        'en_seguimiento'   => 'En seguimiento',
                        'pendiente_cierre' => 'Pendiente de cierre',
                        'convertido'       => 'Convertido',
                        'descartado'       => 'Descartado',
                        default            => $state,
                    }),

                Tables\Columns\TextColumn::make('origen')
                    ->label('Origen')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Último movimiento')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Contacto $record) => route('filament.admin.resources.contactos.edit', $record))
                    ->openUrlInNewTab(false),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
