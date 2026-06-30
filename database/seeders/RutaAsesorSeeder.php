<?php

namespace Database\Seeders;

use App\Models\RoutePoint;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RutaAsesorSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener un asesor existente
        $asesor = User::role('asesor')->first();

        if (! $asesor) {
            $this->command->warn('No se encontró ningún asesor. Ejecuta AsesorSeeder primero.');
            return;
        }

        $this->command->info("Creando ruta de prueba para: {$asesor->name} (ID: {$asesor->id})");

        // Eliminar puntos existentes del asesor para hoy
        RoutePoint::where('user_id', $asesor->id)
            ->whereDate('timestamp', now()->toDateString())
            ->delete();

        // Generar ruta realista por CDMX
        $puntos = $this->generarRutaCDMX(now());

        // Insertar puntos
        foreach ($puntos as $punto) {
            RoutePoint::create([
                'user_id'   => $asesor->id,
                'lat'       => $punto['lat'],
                'lng'       => $punto['lng'],
                'precision' => $punto['precision'],
                'velocidad' => $punto['velocidad'],
                'timestamp' => $punto['timestamp'],
                'synced_at' => now(),
            ]);
        }

        $this->command->info("Se crearon {$asesor->id} " . count($puntos) . ' puntos de ruta.');

        // También crear puntos para ayer (para ver selector de fechas)
        $ayer = now()->subDay();
        RoutePoint::where('user_id', $asesor->id)
            ->whereDate('timestamp', $ayer->toDateString())
            ->delete();

        $puntosAyer = $this->generarRutaCDMX($ayer);
        foreach ($puntosAyer as $punto) {
            RoutePoint::create([
                'user_id'   => $asesor->id,
                'lat'       => $punto['lat'],
                'lng'       => $punto['lng'],
                'precision' => $punto['precision'],
                'velocidad' => $punto['velocidad'],
                'timestamp' => $punto['timestamp'],
                'synced_at' => now(),
            ]);
        }

        $this->command->info("También se creó ruta de ayer ({$ayer->toDateString()}) con " . count($puntosAyer) . ' puntos.');
    }

    /**
     * Genera una ruta realista por CDMX simulando un día de trabajo.
     * Puntos desde las 9am hasta las 6pm con pausas y visitas.
     */
    private function generarRutaCDMX(\Carbon\Carbon $fecha): array
    {
        $puntos = [];
        $timestamp = $fecha->copy()->setTime(9, 0, 0);

        // Ubicaciones de visita simuladas (coords aproximadas)
        $ubicaciones = [
            // Oficina → Polanco
            ['lat' => 19.4326, 'lng' => -99.1332, 'nombre' => 'Oficina'],
            ['lat' => 19.4285, 'lng' => -99.1620, 'nombre' => 'Polanco'],
            // Polanco → Condesa
            ['lat' => 19.4285, 'lng' => -99.1620, 'nombre' => 'Polanco'],
            ['lat' => 19.4130, 'lng' => -99.1680, 'nombre' => 'Condesa'],
            // Condesa → Roma
            ['lat' => 19.4130, 'lng' => -99.1680, 'nombre' => 'Condesa'],
            ['lat' => 19.4195, 'lng' => -99.1585, 'nombre' => 'Roma Norte'],
            // Roma → Del Valle
            ['lat' => 19.4195, 'lng' => -99.1585, 'nombre' => 'Roma Norte'],
            ['lat' => 19.3967, 'lng' => -99.1747, 'nombre' => 'Del Valle'],
            // Del Valle → UNAM
            ['lat' => 19.3967, 'lng' => -99.1747, 'nombre' => 'Del Valle'],
            ['lat' => 19.3320, 'lng' => -99.1910, 'nombre' => 'Coyoacán'],
            // Lunch break en Coyoacán
            // Coyoacán → Tlalpan
            ['lat' => 19.3320, 'lng' => -99.1910, 'nombre' => 'Coyoacán'],
            ['lat' => 19.2950, 'lng' => -99.1620, 'nombre' => 'Tlalpan Centro'],
            // Tlalpan → Centro
            ['lat' => 19.2950, 'lng' => -99.1620, 'nombre' => 'Tlalpan Centro'],
            ['lat' => 19.3580, 'lng' => -99.1520, 'nombre' => 'Centro Histórico'],
            // Centro → Garibaldi
            ['lat' => 19.3580, 'lng' => -99.1520, 'nombre' => 'Centro Histórico'],
            ['lat' => 19.3700, 'lng' => -99.1410, 'nombre' => 'Garibaldi'],
            // Garibaldi → Indios Verdes
            ['lat' => 19.3700, 'lng' => -99.1410, 'nombre' => 'Garibaldi'],
            ['lat' => 19.3980, 'lng' => -99.1290, 'nombre' => 'Indios Verdes'],
            // Indios Verdes → Aragon
            ['lat' => 19.3980, 'lng' => -99.1290, 'nombre' => 'Indios Verdes'],
            ['lat' => 19.4420, 'lng' => -99.1180, 'nombre' => 'Aragón'],
            // Aragon → Ecatepec
            ['lat' => 19.4420, 'lng' => -99.1180, 'nombre' => 'Aragón'],
            ['lat' => 19.4680, 'lng' => -99.0420, 'nombre' => 'Ecatepec'],
        ];

        $precisionBase = 8;
        $velocidadBase = 30 / 3.6; // 30 km/h en m/s

        foreach ($ubicaciones as $i => $ubicacion) {
            // Si es la primera, solo insertar el punto
            if ($i === 0) {
                $puntos[] = [
                    'lat'       => $ubicacion['lat'],
                    'lng'       => $ubicacion['lng'],
                    'precision' => $precisionBase,
                    'velocidad' => 0,
                    'timestamp' => $timestamp->toDateTimeString(),
                ];
                $timestamp->addMinutes(15);
                continue;
            }

            // Generar puntos intermedios hasta la siguiente ubicación
            $anterior = $ubicaciones[$i - 1];
            $pasos = rand(3, 6); // 3-6 puntos intermedios

            for ($j = 0; $j <= $pasos; $j++) {
                $ratio = $j / max(1, $pasos);
                $lat = $anterior['lat'] + ($ubicacion['lat'] - $anterior['lat']) * $ratio;
                $lng = $anterior['lng'] + ($ubicacion['lng'] - $anterior['lng']) * $ratio;

                // Agregar pequeña variación para simular GPS real
                $lat += (rand(-100, 100) / 100000);
                $lng += (rand(-100, 100) / 100000);

                $velocidad = $j === 0 || $j === $pasos ? 0 : $velocidadBase + (rand(-20, 20) / 10);
                $precision = $precisionBase + rand(-3, 5);

                $puntos[] = [
                    'lat'       => round($lat, 6),
                    'lng'       => round($lng, 6),
                    'precision' => max(3, $precision),
                    'velocidad' => max(0, $velocidad),
                    'timestamp' => $timestamp->toDateTimeString(),
                ];

                // Tiempo entre puntos: 2-5 minutos
                $timestamp->addMinutes(rand(2, 5));
            }

            // Pausa en cada ubicación (5-15 min)
            $timestamp->addMinutes(rand(5, 15));
        }

        return $puntos;
    }
}
