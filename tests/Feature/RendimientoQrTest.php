<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Models\Contacto;
use App\Models\EscaneoQr;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Support\MetricasQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Página «Rendimiento de QR»: embudo por asesor y por medio en un rango de fechas. */
class RendimientoQrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $juan;
    private User $ana;
    private CodigoQr $lonaJuan;
    private CodigoQr $tarjetaJuan;
    private CodigoQr $lonaAna;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 12:00:00');

        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        $this->juan = User::factory()->create(['activo' => true, 'name' => 'Juan Pérez']);
        $this->ana  = User::factory()->create(['activo' => true, 'name' => 'Ana López']);
        $this->juan->assignRole('asesor');
        $this->ana->assignRole('asesor');

        $this->lonaJuan    = CodigoQr::create(['asesor_id' => $this->juan->id, 'codigo' => 'JUAN01', 'medio' => 'lona']);
        $this->tarjetaJuan = CodigoQr::create(['asesor_id' => $this->juan->id, 'codigo' => 'JUAN02', 'medio' => 'tarjeta']);
        $this->lonaAna     = CodigoQr::create(['asesor_id' => $this->ana->id,  'codigo' => 'ANA001', 'medio' => 'lona']);

        // Juan lona: 3 escaneos recientes + 1 viejo (fuera de rango); Juan tarjeta: 1; Ana: 2
        foreach ([2, 5, 9] as $d) $this->escaneo($this->lonaJuan, $d);
        $this->escaneo($this->lonaJuan, 45);
        $this->escaneo($this->tarjetaJuan, 1);
        foreach ([3, 4] as $d) $this->escaneo($this->lonaAna, $d);

        // Juan lona: 1 registro → 1 solicitud → 1 expediente
        $acr = Acreditado::create(['name' => 'María', 'email' => 'maria@example.com', 'password' => 'secreto123', 'codigo_qr_id' => $this->lonaJuan->id, 'asesor_id' => $this->juan->id, 'created_at' => now()->subDays(4)]);
        $contacto = Contacto::withoutEvents(fn () => Contacto::create([
            'nombre' => 'María', 'telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'qr',
            'asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->lonaJuan->id, 'created_at' => now()->subDays(3),
        ]));
        $tipo  = TipoTramite::create(['nombre' => 'FOVISSSTE', 'slug' => 'fov-rq', 'porcentaje_honorarios' => 8]);
        $etapa = EtapaTramite::create(['tipo_tramite_id' => $tipo->id, 'nombre' => 'Inicio', 'orden' => 1]);
        Expediente::create([
            'contacto_id' => $contacto->id, 'asesor_id' => $this->juan->id, 'acreditado_nombre' => 'María',
            'tipo_tramite_id' => $tipo->id, 'etapa_tramite_id' => $etapa->id, 'estado' => 'en_proceso', 'created_at' => now()->subDays(1),
        ]);
    }

    private function escaneo(CodigoQr $qr, int $haceDias): void
    {
        EscaneoQr::create(['codigo_qr_id' => $qr->id, 'plataforma' => 'android', 'ip' => '1.1.1.1', 'created_at' => now()->subDays($haceDias)]);
    }

    public function test_totales_del_rango_y_conversion(): void
    {
        $m = new MetricasQr(now()->subDays(29), now());
        $t = $m->totales();

        $this->assertSame(6, $t['escaneos'], 'el escaneo de hace 45 días queda fuera');
        $this->assertSame(1, $t['registros']);
        $this->assertSame(1, $t['solicitudes']);
        $this->assertSame(1, $t['expedientes']);
        $this->assertSame(16.7, $t['conversion']);
        $this->assertSame(3, $t['qrs']);
    }

    public function test_por_asesor_suma_todos_sus_qr_y_ordena_por_escaneos(): void
    {
        $filas = (new MetricasQr(now()->subDays(29), now()))->porAsesor();

        $this->assertSame(['Juan Pérez', 'Ana López'], $filas->pluck('nombre')->all());
        $juan = $filas->first();
        $this->assertSame(2, $juan['qrs']);
        $this->assertSame(4, $juan['escaneos']);
        $this->assertSame(1, $juan['expedientes']);
        $this->assertSame(25.0, $juan['conversion']);
        $this->assertSame(0, $filas->last()['expedientes']);
    }

    public function test_por_medio_compara_lona_contra_tarjeta(): void
    {
        $filas = (new MetricasQr(now()->subDays(29), now()))->porMedio()->keyBy('clave');

        $this->assertSame(5, $filas['lona']['escaneos']);
        $this->assertSame(2, $filas['lona']['qrs']);
        $this->assertSame(1, $filas['tarjeta']['escaneos']);
        $this->assertSame('Tarjeta de presentación', $filas['tarjeta']['nombre']);
    }

    public function test_filtros_por_asesor_medio_y_rango(): void
    {
        $soloAna = new MetricasQr(now()->subDays(29), now(), $this->ana->id);
        $this->assertSame(2, $soloAna->totales()['escaneos']);

        $soloTarjeta = new MetricasQr(now()->subDays(29), now(), null, 'tarjeta');
        $this->assertSame(1, $soloTarjeta->totales()['escaneos']);

        $ultimos3 = new MetricasQr(now()->subDays(3), now());
        $this->assertSame(3, $ultimos3->totales()['escaneos']);   // hace 3 (desde las 00:00), hace 2 y hace 1
    }

    public function test_serie_semanal_cubre_el_rango_y_cuadra_con_los_totales(): void
    {
        $s = (new MetricasQr(now()->subDays(29), now()))->porSemana();

        $this->assertGreaterThanOrEqual(5, count($s['labels']));
        $this->assertSame(6, array_sum($s['series']['escaneos']));
        $this->assertSame(1, array_sum($s['series']['expedientes']));
        $this->assertCount(count($s['labels']), $s['series']['registros']);
    }

    public function test_sin_ningun_qr_la_pagina_y_las_metricas_salen_en_cero(): void
    {
        \App\Models\CodigoQr::query()->delete();

        $t = (new MetricasQr(now()->subDays(29), now()))->totales();
        $this->assertSame(0, $t['escaneos']);
        $this->assertSame(0, $t['sin_atender']);
        $this->assertNull($t['respuesta_horas']);
        $this->assertSame([], (new MetricasQr(now()->subDays(29), now()))->porAsesor()->all());

        $this->actingAs($this->admin)->get('/admin/rendimiento-qr')->assertOk()->assertSee('Sin QR en este filtro');
    }

    public function test_la_pagina_carga_para_admin_con_las_dos_tablas(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/rendimiento-qr')->assertOk()->getContent();

        $this->assertStringContainsString('Por asesor', $html);
        $this->assertStringContainsString('Por medio', $html);
        $this->assertStringContainsString('Juan Pérez', $html);
        $this->assertStringContainsString('Ana López', $html);
    }

    public function test_el_asesor_solo_ve_lo_suyo_aunque_pida_otro_asesor(): void
    {
        $this->actingAs($this->juan);

        Livewire::test(\App\Filament\Pages\RendimientoQr::class)
            ->assertOk()
            ->assertSee('Juan Pérez')
            ->assertDontSee('Ana López')
            ->set('filtros.asesor_id', $this->ana->id)
            ->assertDontSee('Ana López');
    }

    public function test_aparece_en_el_menu_crm_para_admin(): void
    {
        $this->actingAs($this->admin)->get('/admin/codigo-qrs')->assertOk()->assertSee('Rendimiento de QR');
    }
}
