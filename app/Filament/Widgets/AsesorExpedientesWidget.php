<?php

namespace App\Filament\Widgets;

use App\Models\Expediente;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class AsesorExpedientesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Mis expedientes')
            ->description('Todos tus trámites activos y recientes.')
            ->query(
                Expediente::query()
                    ->where('asesor_id', Auth::id())
                    ->orderByDesc('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('acreditado_nombre')
                    ->label('Acreditado')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tipoTramite.nombre')
                    ->label('Trámite')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('etapa.nombre')
                    ->label('Etapa')
                    ->limit(25)
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'primary' => 'en_proceso',
                        'info'    => 'aprobado',
                        'success' => 'firmado',
                        'gray'    => 'cerrado',
                        'warning' => 'pausado',
                        'danger'  => 'cancelado',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'en_proceso' => 'En proceso',
                        'aprobado'   => 'Aprobado',
                        'firmado'    => 'Firmado',
                        'cerrado'    => 'Cerrado',
                        'pausado'    => 'Pausado',
                        'cancelado'  => 'Cancelado',
                        default      => $state,
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Último movimiento')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Expediente $record) => route('filament.admin.resources.expedientes.edit', $record))
                    ->openUrlInNewTab(false),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
