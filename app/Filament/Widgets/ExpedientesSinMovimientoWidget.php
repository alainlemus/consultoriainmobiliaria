<?php

namespace App\Filament\Widgets;

use App\Models\Expediente;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpedientesSinMovimientoWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Expedientes sin movimiento (+7 días)';
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Expediente::query()
                    ->with(['tipoTramite:id,nombre', 'etapa:id,nombre', 'asesor:id,name'])
                    ->whereIn('estado', ['en_proceso', 'aprobado', 'firmado'])
                    // Sin seguimientos en los últimos 7 días (incluye los que nunca tuvieron ninguno)
                    ->whereDoesntHave('seguimientos', fn ($sq) =>
                        $sq->where('created_at', '>=', now()->subDays(7))
                    )
                    ->orderBy('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('folio')
                    ->label('Folio')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('acreditado_nombre')
                    ->label('Acreditado'),

                Tables\Columns\TextColumn::make('tipoTramite.nombre')
                    ->label('Tipo')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('etapa.nombre')
                    ->label('Etapa'),

                Tables\Columns\TextColumn::make('asesor.name')
                    ->label('Asesor'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Último cambio')
                    ->since()
                    ->color('danger'),
            ])
            ->actions([
                \Filament\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn ($record) => url("/admin/expedientes/{$record->id}/edit")),
            ])
            ->paginated([5, 10, 25]);
    }
}
