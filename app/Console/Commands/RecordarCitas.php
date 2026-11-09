<?php

namespace App\Console\Commands;

use App\Services\CitaService;
use Illuminate\Console\Command;

/** Recordatorios de citas confirmadas: un día antes y dos horas antes. Corre cada hora. */
class RecordarCitas extends Command
{
    protected $signature   = 'citas:recordar';
    protected $description = 'Envía recordatorios de citas confirmadas (24 h y 2 h antes)';

    public function handle(CitaService $service): int
    {
        $n = $service->recordar();
        $this->info("Recordatorios enviados: {$n}");

        return self::SUCCESS;
    }
}
