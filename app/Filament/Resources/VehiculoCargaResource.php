<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoCargaResource\Pages;
use App\Models\VehiculoCarga;
use App\Services\VehiculoService;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/** Cargas de gasolina de los vehículos: forma de pago y reembolsos a los asesores. */
class VehiculoCargaResource extends Resource
{
    protected static ?string $model = VehiculoCarga::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Gasolina';

    protected static string|\UnitEnum|null $navigationGroup = 'Vehículos';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Carga de gasolina';

    protected static ?string $pluralModelLabel = 'Cargas de gasolina';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function canCreate(): bool
    {
        return false;
    }   // las registra el asesor desde la app

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['vehiculo', 'user', 'movimiento.fotos', 'reembolsadoPor']);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::user()?->hasRole('super_admin')) {
            return null;
        }
        $n = VehiculoCarga::where('pago', 'reembolso')->whereNull('reembolsado_at')->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y g:i a', VehiculoService::TZ)->sortable(),
                Tables\Columns\TextColumn::make('vehiculo.nombre')->label('Vehículo'),
                Tables\Columns\TextColumn::make('user.name')->label('Asesor')->searchable(),
                Tables\Columns\TextColumn::make('monto')->label('Monto')->money('MXN')->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('MXN')->label('Total')),
                Tables\Columns\TextColumn::make('litros')->label('Litros')->numeric(2),
                Tables\Columns\TextColumn::make('km')->label('Km')->numeric(),
                Tables\Columns\TextColumn::make('pago')->label('Pago')->badge()
                    ->formatStateUsing(fn ($state) => VehiculoCarga::PAGOS[$state] ?? $state)
                    ->color(fn ($state) => $state === 'reembolso' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('reembolso')->label('Reembolso')->badge()
                    ->getStateUsing(fn (VehiculoCarga $record) => match ($record->reembolsoEstado()) {
                        'pendiente' => 'Pendiente', 'pagado' => 'Pagado', default => '—'
                    })
                    ->color(fn ($state) => match ($state) {
                        'Pendiente' => 'danger', 'Pagado' => 'success', default => 'gray'
                    })
                    ->description(fn (VehiculoCarga $record) => $record->reembolsado_at ? $record->reembolsado_at->setTimezone(VehiculoService::TZ)->format('d/m/Y') : null),
                Tables\Columns\TextColumn::make('ticket')->label('Ticket')
                    ->getStateUsing(fn (VehiculoCarga $record) => $record->movimiento->fotos->firstWhere('clave', 'ticket') ? 'Ver ticket' : '—')
                    ->url(fn (VehiculoCarga $record) => $record->movimiento->fotos->firstWhere('clave', 'ticket')?->url(), true)
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pago')->label('Forma de pago')->options(VehiculoCarga::PAGOS),
                Tables\Filters\Filter::make('pendientes')->label('Reembolsos pendientes')
                    ->query(fn (Builder $query) => $query->where('pago', 'reembolso')->whereNull('reembolsado_at')),
                Tables\Filters\SelectFilter::make('vehiculo')->label('Vehículo')->relationship('vehiculo', 'nombre'),
            ])
            ->actions([
                Action::make('pagado')->label('Marcar pagado')->icon('heroicon-o-check')->color('success')
                    ->visible(fn (VehiculoCarga $record) => $record->reembolsoEstado() === 'pendiente')
                    ->requiresConfirmation()
                    ->modalDescription(fn (VehiculoCarga $record) => 'Reembolso de $'.number_format((float) $record->monto, 2)." a {$record->user->name}. Le llegará un aviso.")
                    ->action(fn (VehiculoCarga $record) => app(VehiculoService::class)->marcarReembolsado($record, Auth::user())),
                Action::make('reserva')->label('Reserva')->icon('heroicon-o-key')->color('gray')
                    ->url(fn (VehiculoCarga $record) => VehiculoReservaResource::getUrl('view', ['record' => $record->reserva_id])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListVehiculoCargas::route('/')];
    }
}
