<?php

namespace App\Filament\Widgets;

use App\Models\Expediente;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RankingAsesoresWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static ?string $heading = 'Ranking de Asesores (mes actual)';
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->role('asesor')
                    ->where('activo', true)
                    ->withCount([
                        'expedientes as activos' => fn (Builder $q) =>
                            $q->whereIn('estado', ['en_proceso', 'aprobado', 'firmado']),
                        'expedientes as cerrados_mes' => fn (Builder $q) =>
                            $q->where('estado', 'cerrado')
                              ->whereMonth('fecha_cierre', now()->month)
                              ->whereYear('fecha_cierre', now()->year),
                    ])
                    ->withSum([
                        'expedientes as honorarios_mes' => fn (Builder $q) =>
                            $q->where('estado', 'cerrado')
                              ->whereMonth('fecha_cierre', now()->month)
                              ->whereYear('fecha_cierre', now()->year),
                    ], 'honorarios_monto')
                    ->orderByDesc('cerrados_mes')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Asesor'),

                Tables\Columns\TextColumn::make('activos')
                    ->label('Activos')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('cerrados_mes')
                    ->label('Cerrados este mes')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('honorarios_mes')
                    ->label('Honorarios mes')
                    ->formatStateUsing(fn ($state) => '$' . number_format((float) $state, 0, '.', ',')),
            ])
            ->paginated(false);
    }
}
