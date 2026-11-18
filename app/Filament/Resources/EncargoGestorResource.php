<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EncargoGestorResource\Pages;
use App\Models\EncargoGestor;
use App\Models\User;
use App\Support\EncargoGestorPayload;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * CRM → Gestores: todos los encargos a gestores en una sola lista (solo super_admin).
 * Se envían y se corrigen desde la pestaña «Gestores» de cada expediente; aquí se
 * ve de un vistazo qué tiene cada gestor, en qué paso va y qué ya se pasó de fecha.
 */
class EncargoGestorResource extends Resource
{
    protected static ?string $model = EncargoGestor::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Gestores';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'encargo a gestor';

    protected static ?string $pluralModelLabel = 'Encargos a gestores';

    protected static ?string $slug = 'encargos-gestor';

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;   // se envían desde el expediente
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /** Encargos abiertos cuya fecha de entrega ya pasó. */
    public static function scopeVencidos(Builder $query): Builder
    {
        return $query->activos()->whereNotNull('fecha_entrega')->whereDate('fecha_entrega', '<', today());
    }

    public static function vencido(EncargoGestor $r): bool
    {
        return EncargoGestorPayload::vencido($r);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }
        $n = static::scopeVencidos(EncargoGestor::query())->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Encargos que ya pasaron su fecha de entrega';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['expediente', 'gestor'])
            ->withCount(['documentos', 'documentosSubidos']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('expediente.folio')->label('Expediente')->weight('bold')->searchable()
                    ->description(fn (EncargoGestor $record) => $record->expediente?->acreditado_nombre),
                Tables\Columns\TextColumn::make('gestor.name')->label('Gestor')->searchable(),
                Tables\Columns\TextColumn::make('instrucciones')->label('Qué tramitar')->limit(50)->wrap()
                    ->tooltip(fn (EncargoGestor $record) => $record->instrucciones),
                Tables\Columns\TextColumn::make('fecha_entrega')->label('Entrega')->date('d M Y')->placeholder('—')->sortable()
                    ->color(fn (EncargoGestor $record) => static::vencido($record) ? 'danger' : null)
                    ->description(fn (EncargoGestor $record) => static::vencido($record) ? 'Vencido' : null),
                Tables\Columns\TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => EncargoGestor::ESTADOS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        EncargoGestor::POR_REVISAR => 'warning',
                        EncargoGestor::CON_OBSERVACIONES => 'danger',
                        EncargoGestor::EN_TRAMITE => 'info',
                        EncargoGestor::POR_VALIDAR => 'warning',
                        EncargoGestor::TERMINADO => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('documentos_count')->label('Compartidos')->alignCenter(),
                Tables\Columns\TextColumn::make('documentos_subidos_count')->label('Subidos')->alignCenter(),
                Tables\Columns\TextColumn::make('updated_at')->label('Último movimiento')->since()->sortable()
                    ->tooltip(fn (EncargoGestor $record) => $record->updated_at?->format('d/m/Y g:i a')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gestor_id')->label('Gestor')
                    ->options(fn () => User::role('gestor')->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('estado')->label('Estado')->options(EncargoGestor::ESTADOS),
                Tables\Filters\Filter::make('abiertos')->label('Solo abiertos')->default()
                    ->query(fn (Builder $query) => $query->activos()),
                Tables\Filters\Filter::make('vencidos')->label('Vencidos')
                    ->query(fn (Builder $query) => static::scopeVencidos($query)),
            ])
            ->actions([
                Action::make('ver')
                    ->label('Revisión e historial')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (EncargoGestor $record) => "Encargo de {$record->gestor?->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (EncargoGestor $record) => view('filament.encargo-gestor-detalle', ['encargo' => $record->load(['documentos', 'documentosSubidos', 'eventos.user'])])),
                Action::make('expediente')
                    ->label('Abrir expediente')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (EncargoGestor $record) => ExpedienteResource::getUrl('edit', ['record' => $record->expediente_id])),
            ])
            ->emptyStateHeading('Sin encargos')
            ->emptyStateDescription('Los expedientes se envían a un gestor desde la pestaña «Gestores» de cada expediente.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEncargosGestor::route('/'),
        ];
    }
}
