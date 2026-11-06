<?php

namespace App\Console\Commands;

use App\Models\EncargoGestor;
use App\Services\EncargoGestorService;
use Illuminate\Console\Command;

/**
 * Recuerda a los super_admin los encargos que el gestor ya entregó («Ya terminé»)
 * y siguen sin validar: a los 2 días de entregados y luego cada 2 días.
 */
class RecordarValidacionEncargos extends Command
{
    public const CADA_DIAS = 2;

    protected $signature = 'encargos:recordar-validacion';

    protected $description = 'Recuerda a los super_admin los encargos de gestor pendientes de validar';

    public function handle(EncargoGestorService $service): int
    {
        $limite = now()->subDays(self::CADA_DIAS)->toDateString();

        $encargos = EncargoGestor::where('estado', EncargoGestor::POR_VALIDAR)
            ->whereDate('entregado_at', '<=', $limite)
            ->where(fn ($q) => $q->whereNull('ultimo_recordatorio_at')->orWhereDate('ultimo_recordatorio_at', '<=', $limite))
            ->with(['gestor:id,name', 'expediente:id,folio'])
            ->get();

        $encargos->each(fn (EncargoGestor $e) => $service->recordarValidacion($e));

        $this->info("Recordatorios enviados: {$encargos->count()}");

        return self::SUCCESS;
    }
}
