<?php

namespace App\Filament\Pages;

use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PanelAsesores extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Panel de Asesores';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 10;
    protected static string  $view            = 'filament.pages.panel-asesores';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    // ── Tabla principal: asesores con métricas ──────────────────────────────

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'asesor'))
            ->withCount([
                'expedientes as total_expedientes',
                'expedientes as expedientes_activos'   => fn ($q) => $q->whereNotIn('estado', ['cerrado','cancelado']),
                'expedientes as expedientes_cerrados'  => fn ($q) => $q->where('estado', 'cerrado'),
            ]);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Asesor')
                ->searchable()
                ->weight('bold')
                ->url(fn (User $record) => route('filament.admin.resources.expedientes.index', [
                    'tableFilters[asesor_id][value]' => $record->id,
                ])),

            Tables\Columns\TextColumn::make('total_expedientes')
                ->label('Total')
                ->alignCenter()
                ->badge()
                ->color('gray'),

            Tables\Columns\TextColumn::make('expedientes_activos')
                ->label('Activos')
                ->alignCenter()
                ->badge()
                ->color('warning'),

            Tables\Columns\TextColumn::make('expedientes_cerrados')
                ->label('Cerrados')
                ->alignCenter()
                ->badge()
                ->color('success'),

            Tables\Columns\TextColumn::make('docs_pendientes')
                ->label('Docs pendientes')
                ->alignCenter()
                ->badge()
                ->color('danger')
                ->state(function (User $record): int {
                    return DocumentoExpediente::whereHas('expediente', fn ($q) => $q->where('asesor_id', $record->id)
                        ->whereNotIn('estado', ['cerrado','cancelado'])
                    )->where('estado', 'pendiente')->count();
                }),

            Tables\Columns\TextColumn::make('etapas_resumen')
                ->label('Distribución por etapa')
                ->state(function (User $record): string {
                    $etapas = Expediente::where('asesor_id', $record->id)
                        ->whereNotIn('estado', ['cerrado','cancelado'])
                        ->with('etapa')
                        ->get()
                        ->groupBy(fn ($e) => $e->etapa?->nombre ?? 'Sin etapa')
                        ->map->count();

                    if ($etapas->isEmpty()) return '—';
                    return $etapas->map(fn ($c, $n) => "{$n}: {$c}")->implode(' | ');
                })
                ->wrap(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('ver_expedientes')
                ->label('Ver expedientes')
                ->icon('heroicon-o-folder-open')
                ->color('primary')
                ->url(fn (User $record) => '/admin/expedientes?tableFilters[asesor_id][value]=' . $record->id),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Seguimiento de asesores';
    }

    // ── Métricas de resumen (para la vista) ─────────────────────────────────

    public function getResumenGlobal(): array
    {
        $expedientes = Expediente::query();

        return [
            'total'             => (clone $expedientes)->count(),
            'activos'           => (clone $expedientes)->whereNotIn('estado', ['cerrado','cancelado'])->count(),
            'cerrados'          => (clone $expedientes)->where('estado', 'cerrado')->count(),
            'docs_pendientes'   => DocumentoExpediente::where('estado', 'pendiente')
                ->whereHas('expediente', fn ($q) => $q->whereNotIn('estado', ['cerrado','cancelado']))
                ->count(),
            'docs_completos'    => Expediente::whereNotIn('estado', ['cerrado','cancelado'])
                ->whereDoesntHave('documentos', fn ($q) => $q->where('estado', 'pendiente'))
                ->count(),
        ];
    }
}
