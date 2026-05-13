<?php

namespace App\Filament\Widgets;

use App\Models\EtapaTramite;
use App\Models\Expediente;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class FunnelEtapasWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $heading = 'Expedientes por Etapa (activos)';
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EtapaTramite::query()
                    ->withCount([
                        'expedientes as total' => fn (Builder $q) =>
                            $q->whereIn('estado', ['en_proceso', 'aprobado', 'firmado']),
                    ])
                    ->having('total', '>', 0)
                    ->orderByDesc('total')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tipoTramite.nombre')
                    ->label('Tipo de trámite')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Etapa'),

                Tables\Columns\TextColumn::make('total')
                    ->label('Expedientes')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 5 ? 'danger' : ($state > 2 ? 'warning' : 'success')),
            ])
            ->paginated(false);
    }
}
