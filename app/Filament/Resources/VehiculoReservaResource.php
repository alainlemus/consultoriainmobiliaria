<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoReservaResource\Pages;
use App\Models\VehiculoReserva;
use App\Services\VehiculoService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Reservas de vehículos. Las crean los asesores desde la app; el super admin
 * solo las consulta (información y fotos) y las autoriza o rechaza.
 */
class VehiculoReservaResource extends Resource
{
    protected static ?string $model = VehiculoReserva::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Reservas';

    protected static string|\UnitEnum|null $navigationGroup = 'Vehículos';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Reserva de vehículo';

    protected static ?string $pluralModelLabel = 'Reservas de vehículos';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public static function canCreate(): bool
    {
        return false;
    }          // las piden los asesores desde la app

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
        return parent::getEloquentQuery()->with(['vehiculo.tipo', 'asesor']);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! Auth::user()?->hasRole('super_admin')) {
            return null;
        }
        $n = VehiculoReserva::where('estado', VehiculoReserva::SOLICITADA)->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function color(string $estado): string
    {
        return match ($estado) {
            VehiculoReserva::SOLICITADA => 'warning',
            VehiculoReserva::EN_ESPERA => 'gray',
            VehiculoReserva::AUTORIZADA => 'success',
            VehiculoReserva::EN_USO, VehiculoReserva::RESGUARDO => 'info',
            VehiculoReserva::ENTREGADA => 'gray',
            default => 'danger',
        };
    }

    public static function table(Table $table): Table
    {
        $svc = app(VehiculoService::class);

        return $table
            ->defaultSort('inicio', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('inicio')->label('Cuándo')->weight('bold')
                    ->getStateUsing(fn (VehiculoReserva $r) => $svc->cuando($r))->wrap()
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('inicio', $direction)),
                Tables\Columns\TextColumn::make('vehiculo.nombre')->label('Vehículo')
                    ->description(fn (VehiculoReserva $r) => $r->vehiculo?->tipo?->nombre),
                Tables\Columns\TextColumn::make('asesor.name')->label('Asesor')->searchable()
                    ->description(fn (VehiculoReserva $r) => $r->asesor?->licenciaVigente() ? null : '⚠️ Sin licencia vigente'),
                Tables\Columns\TextColumn::make('motivo')->label('Motivo')->limit(50)->wrap()
                    ->description(fn (VehiculoReserva $record) => $record->alertas ? '⚠️ '.count($record->alertas).' alerta(s) por revisar' : null),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn ($state, VehiculoReserva $record) => (VehiculoReserva::ESTADOS[$state] ?? $state).($state === VehiculoReserva::EN_ESPERA ? ' #'.$svc->posicionFila($record) : ''))
                    ->color(fn ($state) => self::color((string) $state)),
                Tables\Columns\TextColumn::make('created_at')->label('Pedida')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->label('Estado')->options(VehiculoReserva::ESTADOS),
                Tables\Filters\SelectFilter::make('vehiculo')->label('Vehículo')->relationship('vehiculo', 'nombre'),
                Tables\Filters\Filter::make('vigentes')->label('Solo pendientes y próximas')->default()
                    ->query(fn (Builder $query) => $query->where(fn (Builder $w) => $w->where('fin', '>=', now()->startOfDay())->orWhereIn('estado', VehiculoReserva::FUERA))
                        ->whereNotIn('estado', [VehiculoReserva::RECHAZADA, VehiculoReserva::CANCELADA])),
            ])
            ->actions([
                ...self::acciones(),
                ViewAction::make()->label('Ver'),
            ]);
    }

    /** Autorizar y rechazar; se usan en la tabla y en el detalle. */
    public static function acciones(): array
    {
        return [
            Action::make('autorizar')->label('Autorizar')->icon('heroicon-o-check')->color('success')
                ->visible(fn (VehiculoReserva $record) => $record->estado === VehiculoReserva::SOLICITADA)
                ->requiresConfirmation()
                ->modalDescription(fn (VehiculoReserva $record) => $record->asesor?->licenciaVigente() ? null : 'Este asesor no tiene licencia vigente cargada. Puedes autorizar de todos modos.')
                ->action(fn (VehiculoReserva $record) => app(VehiculoService::class)->autorizar($record, Auth::user())),
            Action::make('priorizar')->label('Dar prioridad')->icon('heroicon-o-arrow-up-circle')->color('warning')
                ->visible(fn (VehiculoReserva $record) => in_array($record->estado, [VehiculoReserva::SOLICITADA, VehiculoReserva::EN_ESPERA], true)
                    && app(VehiculoService::class)->conflictos($record)->isNotEmpty())
                ->modalHeading('Dar prioridad a esta visita')
                ->modalDescription(fn (VehiculoReserva $record) => 'Se autoriza esta reserva y quedan en espera: '
                    .app(VehiculoService::class)->conflictos($record)->map(fn ($c) => $c->asesor->name.' ('.(VehiculoReserva::ESTADOS[$c->estado] ?? $c->estado).')')->join(', ')
                    .'. A cada uno le llega el motivo y, si hay otro vehículo libre, se le ofrece.')
                ->form([Forms\Components\TextInput::make('motivo')->label('Motivo (les llega a los asesores)')->required()->maxLength(255)->placeholder('Firma con cliente')])
                ->action(fn (VehiculoReserva $record, array $data) => app(VehiculoService::class)->priorizar($record, Auth::user(), $data['motivo'])),
            Action::make('rechazar')->label('Rechazar')->icon('heroicon-o-x-mark')->color('danger')
                ->visible(fn (VehiculoReserva $record) => $record->estaAbierta())
                ->form([Forms\Components\TextInput::make('motivo')->label('Motivo (le llega al asesor)')->maxLength(255)])
                ->action(fn (VehiculoReserva $record, array $data) => app(VehiculoService::class)->rechazar($record, Auth::user(), $data['motivo'] ?? null)),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehiculoReservas::route('/'),
            'view' => Pages\ViewVehiculoReserva::route('/{record}'),
        ];
    }
}
