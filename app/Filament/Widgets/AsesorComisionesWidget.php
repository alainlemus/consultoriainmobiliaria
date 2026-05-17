<?php

namespace App\Filament\Widgets;

use App\Models\Comision;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class AsesorComisionesWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('asesor');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Mis comisiones')
            ->description('Historial de comisiones generadas por tus expedientes cerrados.')
            ->query(
                Comision::query()
                    ->where('asesor_id', Auth::id())
                    ->with('expediente')
                    ->orderByDesc('fecha_generacion')
            )
            ->columns([
                Tables\Columns\TextColumn::make('expediente.folio')
                    ->label('Expediente')
                    ->weight('bold')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('expediente.acreditado_nombre')
                    ->label('Acreditado')
                    ->limit(30)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('monto_comision')
                    ->label('Comisión')
                    ->money('MXN')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pendiente',
                        'info'    => 'aprobada',
                        'success' => 'pagada',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'aprobada'  => 'Aprobada',
                        'pagada'    => 'Pagada',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('fecha_generacion')
                    ->label('Generada')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('fecha_pago')
                    ->label('Fecha de pago')
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
