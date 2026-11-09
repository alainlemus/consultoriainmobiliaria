<?php

namespace App\Filament\Pages;

use App\Models\CodigoQr;
use App\Models\User;
use App\Support\MetricasQr;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Rendimiento de los QR impresos: cuánto trae cada asesor y cada medio
 * (lona, tarjeta, panfleto…) en un rango de fechas. El asesor solo ve lo suyo.
 */
class RendimientoQr extends Page
{
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Rendimiento de QR';
    protected static ?string $title           = 'Rendimiento de QR';
    protected static string | \UnitEnum | null $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 6;
    protected string $view = 'filament.pages.rendimiento-qr';

    public ?array $filtros = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'asesor']);
    }

    public function mount(): void
    {
        $this->form->fill([
            'desde'     => now()->subDays(29)->toDateString(),
            'hasta'     => now()->toDateString(),
            'asesor_id' => $this->esAsesor() ? Auth::id() : null,
            'medio'     => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        Forms\Components\DatePicker::make('desde')->label('Desde')->native(false)->displayFormat('d/m/Y')->live()->required(),
                        Forms\Components\DatePicker::make('hasta')->label('Hasta')->native(false)->displayFormat('d/m/Y')->live()->required(),
                        Forms\Components\Select::make('asesor_id')
                            ->label('Asesor')
                            ->placeholder('Todos')
                            ->options(User::role('asesor')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->visible(fn () => ! $this->esAsesor()),
                        Forms\Components\Select::make('medio')
                            ->label('Medio')
                            ->placeholder('Todos')
                            ->options(CodigoQr::MEDIOS)
                            ->live(),
                    ])->columns(4),
            ])
            ->statePath('filtros');
    }

    public function metricas(): MetricasQr
    {
        $f = $this->filtros ?? [];
        $desde = Carbon::parse($f['desde'] ?? now()->subDays(29)->toDateString());
        $hasta = Carbon::parse($f['hasta'] ?? now()->toDateString());
        if ($hasta->lt($desde)) [$desde, $hasta] = [$hasta, $desde];

        // El asesor nunca ve más que lo suyo, diga lo que diga el formulario.
        $asesorId = $this->esAsesor() ? Auth::id() : (isset($f['asesor_id']) && $f['asesor_id'] !== '' ? (int) $f['asesor_id'] : null);

        return new MetricasQr($desde, $hasta, $asesorId, $f['medio'] ?: null);
    }

    /** Parámetros para montar la gráfica con los mismos filtros. */
    public function parametrosGrafica(): array
    {
        $f = $this->filtros ?? [];
        return [
            'desde'    => $f['desde'] ?? now()->subDays(29)->toDateString(),
            'hasta'    => $f['hasta'] ?? now()->toDateString(),
            'asesorId' => $this->esAsesor() ? Auth::id() : (isset($f['asesor_id']) && $f['asesor_id'] !== '' ? (int) $f['asesor_id'] : null),
            'medio'    => $f['medio'] ?: null,
        ];
    }

    public function esAsesor(): bool
    {
        return Auth::user()?->hasRole('asesor') && ! Auth::user()->hasRole('super_admin');
    }
}
