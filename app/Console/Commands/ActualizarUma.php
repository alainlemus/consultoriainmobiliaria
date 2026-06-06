<?php

namespace App\Console\Commands;

use App\Services\UmaService;
use Illuminate\Console\Command;

class ActualizarUma extends Command
{
    protected $signature = 'uma:actualizar
                            {--forzar : Actualiza aunque no sea febrero}
                            {--solo-mostrar : Solo muestra el valor actual sin actualizar}';

    protected $description = 'Actualiza el valor de la UMA desde INEGI (API oficial o scraping)';

    public function handle(): int
    {
        if ($this->option('solo-mostrar')) {
            $this->mostrarValorActual();
            return 0;
        }

        $esFebrero = now()->month === 2;
        $forzar    = $this->option('forzar');

        if (! $esFebrero && ! $forzar) {
            $this->line('');
            $this->info('ℹ️  La UMA se actualiza el 1 de febrero de cada año.');
            $this->line('   Usa <comment>--forzar</comment> para actualizar de todas formas.');
            $this->line('');
            $this->mostrarValorActual();
            return 0;
        }

        $this->line('');
        $this->info('🔄 Consultando valor de la UMA en INEGI...');

        $resultado = UmaService::actualizar();

        $this->line('');

        if ($resultado['datos']) {
            $this->info('✅ UMA actualizada correctamente');
            $this->line("   Fuente    : <comment>{$resultado['fuente']}</comment>");
            $this->mostrarValorActual();
        } else {
            $this->warn('⚠️  No se pudo obtener el valor desde fuentes externas.');
            $this->warn('   Verifica tu INEGI_TOKEN en .env o la conexión a internet.');
            $this->line('');
            $this->line('   Valor actual en uso (BD o respaldo):');
            $this->mostrarValorActual();
        }

        return 0;
    }

    private function mostrarValorActual(): void
    {
        $info = UmaService::info();
        $this->table(
            ['Concepto', 'Valor', 'Vigencia'],
            [
                ['UMA Diaria',   '$' . number_format($info['diaria'],  2), $info['vigencia']],
                ['UMA Mensual',  '$' . number_format($info['mensual'], 2), $info['vigencia']],
                ['UMA Anual',    '$' . number_format($info['anual'],   2), $info['vigencia']],
            ]
        );
    }
}
