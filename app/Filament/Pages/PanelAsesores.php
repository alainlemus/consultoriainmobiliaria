<?php

namespace App\Filament\Pages;

use App\Models\DocumentoExpediente;
use App\Models\Contacto;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class PanelAsesores extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Panel de Asesores';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 20;
    protected string $view = 'filament.pages.panel-asesores';

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
                'contactos as total_prospectos' => fn ($q) => $q->where('estado_prospecto', '!=', 'descartado'),
                'contactos as prospectos_sin_documentos' => fn ($q) => $q->where('estado_prospecto', '!=', 'descartado')
                    ->whereDoesntHave('documentos', fn ($d) => $d->whereNotNull('ruta_archivo')),
                'documentos as total_documentos' => fn ($q) => $q->whereNotNull('documentos_expediente.ruta_archivo'),
                'documentos as documentos_por_capturar' => fn ($q) => $q->whereNotNull('documentos_expediente.ruta_archivo')
                    ->whereNull('documentos_expediente.capturado_at'),
            ])
            ->orderByDesc('total_prospectos');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Asesor')
                ->searchable()
                ->weight('bold')
                ->url(fn (User $record) => route('filament.admin.resources.contactos.index', [
                    'tableFilters[asesor_id][value]' => $record->id,
                ])),

            Tables\Columns\TextColumn::make('total_prospectos')
                ->label('Prospectos')
                ->alignCenter()
                ->badge()
                ->color('gray'),

            Tables\Columns\TextColumn::make('prospectos_sin_documentos')
                ->label('Sin documentos')
                ->alignCenter()
                ->badge()
                ->color('warning'),

            Tables\Columns\TextColumn::make('total_documentos')
                ->label('Documentos subidos')
                ->alignCenter()
                ->badge()
                ->color('success'),

            Tables\Columns\TextColumn::make('documentos_por_capturar')
                ->label('Por capturar')
                ->alignCenter()
                ->badge()
                ->color('danger'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            \Filament\Actions\Action::make('ver_expedientes')
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
        $activos = Contacto::where('estado_prospecto', '!=', 'descartado');
        $conArchivo = DocumentoExpediente::whereNotNull('ruta_archivo');

        return [
            'prospectos'      => (clone $activos)->count(),
            'sin_documentos'  => (clone $activos)->whereDoesntHave('documentos', fn ($q) => $q->whereNotNull('ruta_archivo'))->count(),
            'documentos'      => (clone $conArchivo)->count(),
            'por_capturar'    => (clone $conArchivo)->whereNull('capturado_at')->count(),
        ];
    }
}
