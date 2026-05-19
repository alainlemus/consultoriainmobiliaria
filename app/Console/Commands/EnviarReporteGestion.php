<?php

namespace App\Console\Commands;

use App\Mail\ReporteGestion;
use App\Models\User;
use App\Notifications\ReporteGestionEnviado;
use App\Services\ReporteGestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Genera y envía el reporte de gestión por email a todos los super_admin.
 *
 * Uso:
 *   php artisan reportes:enviar --tipo=diario
 *   php artisan reportes:enviar --tipo=semanal
 *   php artisan reportes:enviar --tipo=mensual
 *
 * El scheduler lo ejecuta automáticamente (ver routes/console.php).
 */
class EnviarReporteGestion extends Command
{
    protected $signature   = 'reportes:enviar {--tipo=diario : Tipo de reporte: diario, semanal, mensual}';
    protected $description = 'Genera y envía el reporte de gestión periódico a los super_admin por email.';

    public function handle(ReporteGestionService $service): int
    {
        $tipo = strtolower($this->option('tipo'));

        if (! in_array($tipo, ['diario', 'semanal', 'mensual'])) {
            $this->error("Tipo inválido '{$tipo}'. Usa: diario, semanal, mensual.");
            return self::FAILURE;
        }

        [$desde, $hasta, $periodoLabel] = $this->calcularPeriodo($tipo);

        $this->info("Generando reporte {$tipo}: {$periodoLabel}...");

        $datos = $service->generar($desde, $hasta, $tipo);

        $admins = User::role('super_admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('No hay super_admin activos. Reporte no enviado.');
            return self::SUCCESS;
        }

        $enviados = 0;
        foreach ($admins as $admin) {
            if (! $admin->email) continue;

            Mail::to($admin->email)
                ->queue(new ReporteGestion(
                    datos:   $datos,
                    tipo:    $tipo,
                    periodo: $periodoLabel,
                ));

            $enviados++;

            // Notificación dentro del panel Filament
            $admin->notify(new ReporteGestionEnviado(
                tipo:          $tipo,
                periodo:       $periodoLabel,
                destinatarios: $enviados,
            ));
        }

        $this->info("✓ Reporte {$tipo} enviado a {$enviados} admin(s).");

        return self::SUCCESS;
    }

    /**
     * Devuelve [desde, hasta, etiqueta] según el tipo.
     * - diario:  ayer 00:00 → ayer 23:59:59
     * - semanal: lunes pasado → domingo pasado
     * - mensual: primer día del mes anterior → último día del mes anterior
     *
     * @return array{Carbon, Carbon, string}
     */
    private function calcularPeriodo(string $tipo): array
    {
        return match($tipo) {
            'diario' => [
                Carbon::yesterday()->startOfDay(),
                Carbon::yesterday()->endOfDay(),
                Carbon::yesterday()->isoFormat('dddd D [de] MMMM, YYYY'),
            ],
            'semanal' => [
                Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY),
                Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY),
                Carbon::now()->subWeek()->startOfWeek()->isoFormat('D MMM')
                    . ' – '
                    . Carbon::now()->subWeek()->endOfWeek()->isoFormat('D MMM YYYY'),
            ],
            'mensual' => [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
                Carbon::now()->subMonth()->isoFormat('MMMM YYYY'),
            ],
        };
    }
}
