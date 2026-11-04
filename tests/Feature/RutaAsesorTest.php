<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ruta del asesor: inicio/fin (route_points) + visitas registradas en el Mapa
 * (ubicaciones), agrupadas por día y hora del centro de México aunque la app
 * mande las horas en UTC y se sincronicen horas después (sin señal).
 */
class RutaAsesorTest extends TestCase
{
    use RefreshDatabase;

    private function asesor(): User
    {
        Role::findOrCreate('asesor', 'web');
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole('asesor');
        Sanctum::actingAs($u);

        return $u;
    }

    public function test_la_ruta_del_dia_une_inicio_visitas_y_fin_en_hora_local(): void
    {
        $asesor = $this->asesor();

        // 4 nov, hora de México (UTC-6): inicio 9:00, visita 13:00 sin señal, fin 19:30
        $this->postJson('/api/v1/routes/points', ['points' => [
            ['lat' => 20.1, 'lng' => -98.7, 'timestamp' => '2026-11-04T15:00:00.000Z'],
            ['lat' => 20.3, 'lng' => -98.9, 'timestamp' => '2026-11-05T01:30:00.000Z'],
        ]])->assertCreated();

        $asesor->ubicaciones()->create([
            'latitud' => 20.2, 'longitud' => -98.8, 'tipo' => 'visita_cliente',
            'visitado_en' => '2026-11-04T19:00:00.000Z',
        ]);

        $puntos = $this->getJson('/api/v1/routes/points?fecha=2026-11-04')->assertOk()->json('data');

        $this->assertSame(['09:00:00', '13:00:00', '19:30:00'], array_column($puntos, 'hora'));
        $this->assertSame(['gps', 'visita_cliente', 'gps'], array_column($puntos, 'tipo'));

        // El día siguiente queda vacío y la lista de días solo trae el 4
        $this->assertSame([], $this->getJson('/api/v1/routes/points?fecha=2026-11-05')->json('data'));
        $this->assertSame(
            [['fecha' => '2026-11-04', 'puntos' => 3]],
            $this->getJson('/api/v1/routes/dias')->assertOk()->json('data'),
        );
    }
}
