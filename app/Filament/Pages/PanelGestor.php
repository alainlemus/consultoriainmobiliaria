<?php

namespace App\Filament\Pages;

use App\Models\EncargoGestor;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Inicio del gestor en el panel web: los expedientes que le envió la
 * administración, con lo que tiene que tramitar y la descarga de los
 * documentos compartidos. La revisión ✓/✗ y la subida se hacen en la app.
 */
class PanelGestor extends Page
{
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Mis expedientes';
    protected static ?string $title           = 'Mis expedientes';
    protected static ?string $slug            = 'gestor';
    protected static ?int    $navigationSort  = -3;

    protected string $view = 'filament.pages.panel-gestor';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('gestor');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /** @return Collection<int, EncargoGestor> */
    public function getEncargos(): Collection
    {
        return auth()->user()->encargosGestor()
            ->with(['expediente.tipoTramite', 'documentos', 'documentosSubidos'])
            ->orderByRaw("CASE estado WHEN 'por_revisar' THEN 1 WHEN 'con_observaciones' THEN 2 WHEN 'en_tramite' THEN 3 WHEN 'por_validar' THEN 4 WHEN 'terminado' THEN 5 ELSE 6 END")
            ->latest()
            ->get();
    }

    public function urlDescarga(int $expedienteId, int $documentoId): string
    {
        return URL::temporarySignedRoute('api.documentos.descargar', now()->addHour(), [
            'expedienteId' => $expedienteId,
            'documentoId'  => $documentoId,
        ]);
    }
}
