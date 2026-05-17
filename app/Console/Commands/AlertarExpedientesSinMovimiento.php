<?php

namespace App\Console\Commands;

use App\Models\Expediente;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotifAction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Recorre expedientes activos sin seguimiento en los últimos N días
 * y envía una notificación Filament al asesor asignado (y a los super_admin).
 *
 * Ejecución: php artisan expedientes:alertar-sin-movimiento
 * Scheduler: diario (ver routes/console.php)
 */
class AlertarExpedientesSinMovimiento extends Command
{
    protected $signature   = 'expedientes:alertar-sin-movimiento {--dias=7 : Días sin movimiento para disparar la alerta}';
    protected $description = 'Notifica a asesores sobre expedientes sin seguimiento en los últimos N días.';

    public function handle(): int
    {
        $dias  = (int) $this->option('dias');
        $corte = Carbon::now()->subDays($dias);

        // Expedientes activos cuyo último seguimiento es anterior al corte
        // (o nunca han tenido seguimiento)
        $expedientes = Expediente::with(['asesor', 'tipoTramite', 'etapa'])
            ->whereIn('estado', ['en_proceso', 'nuevo'])
            ->whereDoesntHave('seguimientos', fn ($q) => $q->where('created_at', '>=', $corte))
            ->get();

        if ($expedientes->isEmpty()) {
            $this->info("Sin expedientes parados más de {$dias} días. Todo en orden.");
            return self::SUCCESS;
        }

        $this->info("Encontrados {$expedientes->count()} expediente(s) sin movimiento en {$dias} días.");

        // Agrupar por asesor para enviar una sola notificación por asesor
        $porAsesor = $expedientes->groupBy('asesor_id');

        foreach ($porAsesor as $asesorId => $grupo) {
            /** @var User|null $asesor */
            $asesor = User::find($asesorId);
            if (! $asesor) continue;

            $lista = $grupo->map(fn ($e) =>
                "· {$e->folio} — {$e->acreditado_nombre} ({$e->tipoTramite?->nombre})"
            )->join("\n");

            $cantidad = $grupo->count();
            $titulo   = "⚠️ {$cantidad} " . ($cantidad === 1 ? 'expediente' : 'expedientes') . " sin movimiento ({$dias}+ días)";

            // Notificación al asesor
            Notification::make()
                ->title($titulo)
                ->body("Estos expedientes no tienen seguimiento registrado en los últimos {$dias} días:\n{$lista}")
                ->warning()
                ->actions([
                    NotifAction::make('ver')
                        ->label('Ver expedientes')
                        ->url(route('filament.admin.resources.expedientes.index'))
                        ->button(),
                ])
                ->sendToDatabase($asesor);

            $this->line("  → Notificado: {$asesor->name} ({$cantidad} expedientes)");
        }

        // También notificar a los super_admin con el resumen global
        $admins = User::role('super_admin')->get();
        if ($admins->isNotEmpty()) {
            $resumen = $expedientes->map(fn ($e) =>
                "· {$e->folio} — {$e->acreditado_nombre} [{$e->asesor?->name}]"
            )->join("\n");

            Notification::make()
                ->title("Resumen: {$expedientes->count()} expediente(s) sin movimiento ({$dias}+ días)")
                ->body($resumen)
                ->warning()
                ->actions([
                    NotifAction::make('ver')
                        ->label('Ver expedientes')
                        ->url(route('filament.admin.resources.expedientes.index'))
                        ->button(),
                ])
                ->sendToDatabase($admins);

            $this->line("  → Super admins notificados.");
        }

        return self::SUCCESS;
    }
}
