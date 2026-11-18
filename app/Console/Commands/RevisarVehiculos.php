<?php

namespace App\Console\Commands;

use App\Services\VehiculoService;
use Illuminate\Console\Command;

/** Recordatorios de salida y regreso, «no se presentó» y alertas de retraso del préstamo de vehículos. */
class RevisarVehiculos extends Command
{
    protected $signature = 'vehiculos:revisar';

    protected $description = 'Recordatorios y alertas de las reservas de vehículos';

    public function handle(VehiculoService $service): int
    {
        $n = $service->revisar();
        $this->info("Recordatorios: {$n['recordatorios']} · No se presentó: {$n['no_presento']} · Retrasos: {$n['retrasos']}");

        return self::SUCCESS;
    }
}
