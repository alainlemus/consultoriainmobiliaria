<?php

namespace App\Console\Commands;

use App\Models\Contacto;
use App\Models\User;
use App\Notifications\ProspectoSinAtender;
use Illuminate\Console\Command;

/**
 * Avisa al asesor (push + panel) y a los super_admin (panel) de los prospectos
 * asignados que llevan más de HORAS en «nuevo». Cada prospecto se vuelve a
 * recordar cada CADA_DIAS días mientras siga sin atender.
 */
class AlertarProspectosSinAtender extends Command
{
    public const HORAS     = 24;
    public const CADA_DIAS = 2;

    protected $signature   = 'prospectos:alertar-sin-atender';
    protected $description = 'Avisa de prospectos asignados que siguen en «nuevo» después de 24 h';

    public function handle(): int
    {
        $limiteAlerta = now()->subDays(self::CADA_DIAS);

        $prospectos = Contacto::query()
            ->with('asesor')
            ->whereNotNull('asesor_id')
            ->where('estado_prospecto', 'nuevo')
            ->whereNull('contactado_at')
            ->where('created_at', '<=', now()->subHours(self::HORAS))
            ->where(fn ($q) => $q->whereNull('alerta_sin_atender_at')->orWhere('alerta_sin_atender_at', '<=', $limiteAlerta))
            ->get();

        $admins = User::role('super_admin')->get();

        foreach ($prospectos as $p) {
            $p->asesor?->notify(new ProspectoSinAtender($p, paraAsesor: true));
            $admins->each(fn (User $a) => $a->notify(new ProspectoSinAtender($p, paraAsesor: false)));
            Contacto::withoutEvents(fn () => $p->updateQuietly(['alerta_sin_atender_at' => now()]));
        }

        $this->info("Alertas enviadas: {$prospectos->count()}");

        return self::SUCCESS;
    }
}
