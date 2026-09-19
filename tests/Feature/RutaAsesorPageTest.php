<?php

namespace Tests\Feature;

use App\Filament\Pages\RutaAsesorPage;
use App\Models\RoutePoint;
use App\Models\Ubicacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La app ya no trackea la ruta en background: solo guarda un punto al
 * activar/desactivar el toggle. El resto del recorrido se arma combinando
 * esos route_points con las ubicaciones (visitas a clientes/escuelas/
 * propiedades), igual que RouteController::getPoints en la API.
 */
class RutaAsesorPageTest extends TestCase
{
    use RefreshDatabase;

    private RutaAsesorPage $page;
    private User $asesor;
    private User $otroAsesor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);

        $this->asesor = User::factory()->create(['activo' => true, 'name' => 'Asesor Uno']);
        $this->asesor->assignRole('asesor');

        $this->otroAsesor = User::factory()->create(['activo' => true, 'name' => 'Asesor Dos']);
        $this->otroAsesor->assignRole('asesor');

        $this->page = new RutaAsesorPage();
    }

    /** @test */
    public function combina_route_points_y_ubicaciones_ordenados_por_hora(): void
    {
        RoutePoint::create([
            'user_id'   => $this->asesor->id,
            'lat'       => 19.40,
            'lng'       => -99.10,
            'precision' => 8,
            'velocidad' => 0,
            'timestamp' => '2026-06-29 09:00:00',
        ]);

        Ubicacion::create([
            'user_id'     => $this->asesor->id,
            'latitud'     => 19.41,
            'longitud'    => -99.11,
            'tipo'        => 'cliente',
            'nombre_lugar'=> 'Casa cliente',
            'visitado_en' => '2026-06-29 09:30:00',
        ]);

        RoutePoint::create([
            'user_id'   => $this->asesor->id,
            'lat'       => 19.42,
            'lng'       => -99.12,
            'precision' => 10,
            'velocidad' => 0,
            'timestamp' => '2026-06-29 10:00:00',
        ]);

        $puntos = $this->page->getRutasAsesor((string) $this->asesor->id, '2026-06-29');

        $this->assertCount(3, $puntos);
        $this->assertSame(['09:00:00', '09:30:00', '10:00:00'], array_column($puntos, 'hora'));
        $this->assertSame(['rp_1', 'ub_1', 'rp_2'], array_column($puntos, 'id'));
    }

    /** @test */
    public function no_mezcla_puntos_de_otro_asesor(): void
    {
        RoutePoint::create([
            'user_id' => $this->asesor->id, 'lat' => 19.40, 'lng' => -99.10,
            'precision' => 8, 'velocidad' => 0, 'timestamp' => '2026-06-29 09:00:00',
        ]);

        RoutePoint::create([
            'user_id' => $this->otroAsesor->id, 'lat' => 19.50, 'lng' => -99.20,
            'precision' => 8, 'velocidad' => 0, 'timestamp' => '2026-06-29 09:05:00',
        ]);

        Ubicacion::create([
            'user_id' => $this->otroAsesor->id, 'latitud' => 19.51, 'longitud' => -99.21,
            'tipo' => 'cliente', 'visitado_en' => '2026-06-29 09:10:00',
        ]);

        $puntos = $this->page->getRutasAsesor((string) $this->asesor->id, '2026-06-29');

        $this->assertCount(1, $puntos);
    }

    /** @test */
    public function un_dia_con_solo_visitas_sin_toggle_de_ruta_aparece_en_dias_disponibles(): void
    {
        Ubicacion::create([
            'user_id'     => $this->asesor->id,
            'latitud'     => 19.41,
            'longitud'    => -99.11,
            'tipo'        => 'escuela',
            'visitado_en' => '2026-07-01 08:00:00',
        ]);

        $dias = $this->page->getDiasDisponiblesAsesor((string) $this->asesor->id);

        $this->assertContains('2026-07-01', $dias);
    }

    /** @test */
    public function get_rutas_todos_agrupa_por_asesor_combinando_ambas_fuentes(): void
    {
        RoutePoint::create([
            'user_id' => $this->asesor->id, 'lat' => 19.40, 'lng' => -99.10,
            'precision' => 8, 'velocidad' => 0, 'timestamp' => '2026-06-29 09:00:00',
        ]);

        Ubicacion::create([
            'user_id' => $this->asesor->id, 'latitud' => 19.41, 'longitud' => -99.11,
            'tipo' => 'cliente', 'visitado_en' => '2026-06-29 09:30:00',
        ]);

        Ubicacion::create([
            'user_id' => $this->otroAsesor->id, 'latitud' => 19.51, 'longitud' => -99.21,
            'tipo' => 'cliente', 'visitado_en' => '2026-06-29 09:10:00',
        ]);

        $rutas = $this->page->getRutasTodos('2026-06-29');

        $this->assertCount(2, $rutas);

        $porNombre = collect($rutas)->keyBy('name');
        $this->assertCount(2, $porNombre['Asesor Uno']['puntos']);
        $this->assertCount(1, $porNombre['Asesor Dos']['puntos']);
    }

    /** @test */
    public function asesor_sin_puntos_ni_visitas_no_aparece_en_dias_ni_rutas(): void
    {
        $this->assertSame([], $this->page->getDiasDisponiblesAsesor((string) $this->asesor->id));
        $this->assertSame([], $this->page->getRutasAsesor((string) $this->asesor->id, '2026-06-29'));
        $this->assertSame([], $this->page->getRutasTodos('2026-06-29'));
    }
}
