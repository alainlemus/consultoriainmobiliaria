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

        // Usar los correos configurados en Ajustes Generales como destino principal
        // (puede haber varios). Si no hay ninguno configurado, caer al email de
        // cada usuario super_admin en BD.
        $correosDestino = setting_email_list('correo_contacto');

        if (! empty($correosDestino)) {
            Mail::to($correosDestino)
                ->queue(new ReporteGestion(
                    datos:   $datos,
                    tipo:    $tipo,
                    periodo: $periodoLabel,
                ));

            $enviados = count($correosDestino);

            foreach ($admins as $admin) {
                $admin->notify(new ReporteGestionEnviado(
                    tipo:          $tipo,
                    periodo:       $periodoLabel,
                    destinatarios: $enviados,
                ));
            }
        } else {
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

                $admin->notify(new ReporteGestionEnviado(
                    tipo:          $tipo,
                    periodo:       $periodoLabel,
                    destinatarios: $enviados,
                ));
            }
        }

        $this->info("✓ Reporte {$tipo} enviado a {$enviados} destinatario(s).");

        return self::SUCCESS;
    }

    /**
     * Devuelve [desde, hasta, etiqueta] según el tipo.
     * - diario:  hoy 00:00 → hoy 23:59:59  (se envía a las 8 PM del mismo día)
     * - semanal: lunes pasado → viernes pasado  (se envía el sábado en la mañana)
     * - mensual: primer día del mes actual → hoy  (se envía el último día del mes a las 8 PM)
     *
     * @return array{Carbon, Carbon, string}
     */
    private function calcularPeriodo(string $tipo): array
    {
        return match($tipo) {
            'diario' => [
                Carbon::today()->startOfDay(),
                Carbon::today()->endOfDay(),
                Carbon::today()->isoFormat('dddd D [de] MMMM, YYYY'),
            ],
            'semanal' => [
                Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY),
                Carbon::now()->subWeek()->endOfWeek(Carbon::FRIDAY),
                Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->isoFormat('D MMM')
                    . ' – '
                    . Carbon::now()->subWeek()->endOfWeek(Carbon::FRIDAY)->isoFormat('D MMM YYYY'),
            ],
            'mensual' => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfDay(),
                Carbon::now()->isoFormat('MMMM YYYY'),
            ],
        };
    }
}
