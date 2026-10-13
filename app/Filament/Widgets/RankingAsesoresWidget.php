<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
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
        $contactosDelMes  = fn (Builder $q) => $q->whereYear('contactos.created_at', now()->year)->whereMonth('contactos.created_at', now()->month);
        $documentosDelMes = fn (Builder $q) => $q->whereNotNull('documentos_expediente.ruta_archivo')
            ->whereYear('documentos_expediente.created_at', now()->year)
            ->whereMonth('documentos_expediente.created_at', now()->month);

        return $table
            ->query(
                User::query()
                    ->role('asesor')
                    ->where('activo', true)
                    ->where(fn ($q) => $q->has('contactos')->orHas('expedientes')) // solo asesores con actividad
                    ->withCount([
                        'contactos as prospectos_mes' => $contactosDelMes,
                        'expedientes as expedientes_total',
                        'documentos as documentos_mes' => $documentosDelMes,
                    ])
                    ->orderByDesc('prospectos_mes')
                    ->orderByDesc('documentos_mes')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Asesor'),

                Tables\Columns\TextColumn::make('prospectos_mes')
                    ->label('Prospectos del mes')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('documentos_mes')
                    ->label('Documentos del mes')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('expedientes_total')
                    ->label('Expedientes en total')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
