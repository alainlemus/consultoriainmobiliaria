<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Utilidad para calcular días hábiles en México.
 *
 * Considera:
 * - Sábados y domingos como no hábiles
 * - Días festivos oficiales de México (fijos y móviles)
 *
 * Días festivos fijos (Art. 74 LFT):
 *   1 ene  — Año Nuevo
 *   5 feb  — Día de la Constitución (primer lunes de febrero)
 *   21 mar — Natalicio de Benito Juárez (tercer lunes de marzo)
 *   1 may  — Día del Trabajo
 *  16 sep  — Día de la Independencia
 *  18 nov  — Revolución Mexicana (tercer lunes de noviembre)
 *   1 dic  — Transmisión del Poder Ejecutivo (cada 6 años — se omite aquí por simplicidad)
 *  25 dic  — Navidad
 */
class DiasHabiles
{
    /**
     * Agrega $dias días hábiles a una fecha dada.
     * Retorna un Carbon con la nueva fecha.
     */
    public static function agregar(Carbon $fecha, int $dias): Carbon
    {
        $resultado = $fecha->copy();
        $agregados = 0;

        while ($agregados < $dias) {
            $resultado->addDay();
            if (static::esHabil($resultado)) {
                $agregados++;
            }
        }

        return $resultado;
    }

    /**
     * Indica si una fecha es día hábil (no es fin de semana ni festivo).
     */
    public static function esHabil(Carbon $fecha): bool
    {
        // Fin de semana
        if ($fecha->isWeekend()) {
            return false;
        }
        // Festivo oficial
        if (static::esFestivo($fecha)) {
            return false;
        }
        return true;
    }

    /**
     * Días festivos oficiales de México para el año de la fecha dada.
     * Los festivos "en lunes" se calculan según la Ley Federal del Trabajo.
     */
    protected static function esFestivo(Carbon $fecha): bool
    {
        $año = $fecha->year;

        $festivos = [
            // ── Fijos ────────────────────────────────────────────────
            Carbon::create($año, 1,  1),   // Año Nuevo
            Carbon::create($año, 5,  1),   // Día del Trabajo
            Carbon::create($año, 9, 16),   // Independencia
            Carbon::create($año, 12, 25),  // Navidad
        ];

        // ── En lunes (traslado) ───────────────────────────────────────
        // Constitución: primer lunes de febrero
        $festivos[] = static::primerLunesDe($año, 2);
        // Benito Juárez: tercer lunes de marzo
        $festivos[] = static::tercerLunesDe($año, 3);
        // Revolución: tercer lunes de noviembre
        $festivos[] = static::tercerLunesDe($año, 11);

        foreach ($festivos as $festivo) {
            if ($festivo->isSameDay($fecha)) {
                return true;
            }
        }

        return false;
    }

    protected static function primerLunesDe(int $año, int $mes): Carbon
    {
        $d = Carbon::create($año, $mes, 1);
        // Avanzar hasta el primer lunes
        while ($d->dayOfWeek !== Carbon::MONDAY) {
            $d->addDay();
        }
        return $d;
    }

    protected static function tercerLunesDe(int $año, int $mes): Carbon
    {
        $d = static::primerLunesDe($año, $mes);
        return $d->addWeeks(2); // 2 semanas después del primer lunes = tercer lunes
    }
}
