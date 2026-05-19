<?php

namespace Tests\Feature;

use App\Filament\Pages\SimuladorPrecalificacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SimuladorPrecalificacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        Permission::firstOrCreate(['name' => 'page_SimuladorPrecalificacion', 'guard_name' => 'web']);
        $this->admin->givePermissionTo('page_SimuladorPrecalificacion');

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');
        $this->asesor->givePermissionTo('page_SimuladorPrecalificacion');
    }

    // ─── Acceso a la página ────────────────────────────────────────────────

    public function test_super_admin_puede_acceder_al_simulador(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/simulador')
            ->assertOk();
    }

    public function test_asesor_puede_acceder_al_simulador(): void
    {
        $this->actingAs($this->asesor)
            ->get('/admin/simulador')
            ->assertOk();
    }

    public function test_invitado_no_puede_acceder_al_simulador(): void
    {
        $this->get('/admin/simulador')
            ->assertRedirect();
    }

    // ─── canAccess ─────────────────────────────────────────────────────────

    public function test_can_access_retorna_true_para_super_admin(): void
    {
        $this->actingAs($this->admin);
        $this->assertTrue(SimuladorPrecalificacion::canAccess());
    }

    public function test_can_access_retorna_true_para_asesor(): void
    {
        $this->actingAs($this->asesor);
        $this->assertTrue(SimuladorPrecalificacion::canAccess());
    }

    // ─── Lógica de cálculo: Tradicional ────────────────────────────────────

    public function test_calcula_correctamente_credito_tradicional(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularTradicional');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador,
            20000,  // sueldo mensual
            50000,  // subcuenta
            5,      // antigüedad
            35,     // edad
            SimuladorPrecalificacion::VSM_MENSUAL
        );

        $this->assertSame('Crédito Tradicional FOVISSSTE', $resultado['tipo']);
        $this->assertTrue($resultado['elegible']);
        $this->assertEmpty($resultado['errores']);
        $this->assertGreaterThan(0, $resultado['monto_credito']);

        // Valor inmueble = crédito + subcuenta
        $this->assertEqualsWithDelta(
            $resultado['monto_credito'] + 50000,
            $resultado['valor_inmueble'],
            0.01
        );

        // Mensualidad ≤ 30% del sueldo
        $this->assertLessThanOrEqual(20000 * 0.30 + 0.01, $resultado['mensualidad']);

        // Honorarios = 8% del monto crédito
        $this->assertEqualsWithDelta(
            $resultado['monto_credito'] * 0.08,
            $resultado['honorarios'],
            0.01
        );
    }

    public function test_tradicional_detecta_edad_mayor_a_70(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularTradicional');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 20000, 0, 5, 71, SimuladorPrecalificacion::VSM_MENSUAL);

        $this->assertFalse($resultado['elegible']);
        $this->assertNotEmpty($resultado['errores']);
    }

    public function test_tradicional_plazo_se_acorta_por_edad(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularTradicional');
        $metodo->setAccessible(true);

        // Edad 50 → plazo = min(30, 70-50) = 20 años
        $r50 = $metodo->invoke($simulador, 20000, 0, 5, 50, SimuladorPrecalificacion::VSM_MENSUAL);
        $this->assertSame(20, $r50['plazo_años']);

        // Edad 35 → plazo = min(30, 70-35) = 30 años
        $r35 = $metodo->invoke($simulador, 20000, 0, 5, 35, SimuladorPrecalificacion::VSM_MENSUAL);
        $this->assertSame(30, $r35['plazo_años']);
    }

    // ─── Lógica de cálculo: Pensionados ────────────────────────────────────

    public function test_calcula_correctamente_credito_pensionados(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularPensionados');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 35000, 0, 55, SimuladorPrecalificacion::VSM_MENSUAL);

        $this->assertSame('Crédito Pensionados FOVISSSTE', $resultado['tipo']);
        $this->assertTrue($resultado['elegible']);
        $this->assertSame(4.0, $resultado['tasa_anual']);

        // Plazo = min(20, 74-55) = 19
        $this->assertSame(19, $resultado['plazo_años']);
    }

    public function test_pensionados_rechaza_edad_menor_47(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularPensionados');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 35000, 0, 40, SimuladorPrecalificacion::VSM_MENSUAL);
        $this->assertFalse($resultado['elegible']);
    }

    public function test_pensionados_rechaza_edad_mayor_74(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularPensionados');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 35000, 0, 75, SimuladorPrecalificacion::VSM_MENSUAL);
        $this->assertFalse($resultado['elegible']);
    }

    // ─── Lógica de cálculo: Conyugal ───────────────────────────────────────

    public function test_conyugal_suma_ambos_sueldos(): void
    {
        $simulador          = new SimuladorPrecalificacion();
        $metodoConyugal     = new \ReflectionMethod($simulador, 'calcularConyugal');
        $metodoTradicional  = new \ReflectionMethod($simulador, 'calcularTradicional');
        $metodoConyugal->setAccessible(true);
        $metodoTradicional->setAccessible(true);

        $vsm = SimuladorPrecalificacion::VSM_MENSUAL;

        $conyugal    = $metodoConyugal->invoke($simulador, 15000, 12000, 0, 5, 35, $vsm);
        $tradicional = $metodoTradicional->invoke($simulador, 27000, 0, 5, 35, $vsm);

        $this->assertSame('Crédito Conyugal FOVISSSTE', $conyugal['tipo']);
        $this->assertEqualsWithDelta(
            $tradicional['monto_credito'],
            $conyugal['monto_credito'],
            1.0
        );
    }

    // ─── Lógica de cálculo: Para Todos ─────────────────────────────────────

    public function test_para_todos_usa_tasa_fija_10_38(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularParaTodos');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 30000, 0, 40);

        $this->assertSame('FOVISSSTE Para Todos (Bancos: HSBC / Banorte / BBVA)', $resultado['tipo']);
        $this->assertEqualsWithDelta(10.38, $resultado['tasa_anual'], 0.001);
    }

    public function test_para_todos_no_supera_tope_maximo(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularParaTodos');
        $metodo->setAccessible(true);

        // Sueldo muy alto — el crédito no debe superar el tope
        $resultado = $metodo->invoke($simulador, 500000, 0, 35);
        $this->assertLessThanOrEqual(SimuladorPrecalificacion::TOPE_PARA_TODOS, $resultado['monto_credito']);
    }

    // ─── Lógica de cálculo: FOVISSSTE-INFONAVIT Individual ────────────────

    public function test_infonavit_individual_retorna_tipo_correcto(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularInfonavitFovissste');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador,
            20000,  // sueldo
            30000,  // subcuenta FOVISSSTE
            50000,  // subcuenta INFONAVIT
            5,      // antigüedad
            35,     // edad
            SimuladorPrecalificacion::VSM_MENSUAL
        );

        $this->assertSame('FOVISSSTE-INFONAVIT Individual (Crédito Unidos)', $resultado['tipo']);
        $this->assertTrue($resultado['elegible']);
        $this->assertEmpty($resultado['errores']);
    }

    public function test_infonavit_individual_credito_no_supera_tope_430_uma(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularInfonavitFovissste');
        $metodo->setAccessible(true);

        // Sueldo muy alto — crédito FOVISSSTE no debe superar tope
        $resultado = $metodo->invoke($simulador, 500000, 0, 0, 5, 35, SimuladorPrecalificacion::VSM_MENSUAL);

        $this->assertLessThanOrEqual(
            SimuladorPrecalificacion::TOPE_INFONAVIT,
            $resultado['monto_credito']
        );
    }

    public function test_infonavit_individual_suma_subcuenta_infonavit_al_financiamiento(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularInfonavitFovissste');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 20000, 10000, 80000, 5, 35, SimuladorPrecalificacion::VSM_MENSUAL);

        // valor_inmueble = crédito FOVISSSTE + subcuenta INFONAVIT
        $this->assertEqualsWithDelta(
            $resultado['monto_credito'] + 80000,
            $resultado['valor_inmueble'],
            0.01
        );
    }

    public function test_infonavit_individual_rechaza_antiguedad_menor_a_2_anos(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularInfonavitFovissste');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 20000, 0, 0, 1, 35, SimuladorPrecalificacion::VSM_MENSUAL);

        $this->assertFalse($resultado['elegible']);
        $this->assertNotEmpty($resultado['errores']);
    }

    public function test_infonavit_individual_rechaza_edad_mayor_70(): void
    {
        $simulador = new SimuladorPrecalificacion();
        $metodo    = new \ReflectionMethod($simulador, 'calcularInfonavitFovissste');
        $metodo->setAccessible(true);

        $resultado = $metodo->invoke($simulador, 20000, 0, 0, 5, 71, SimuladorPrecalificacion::VSM_MENSUAL);

        $this->assertFalse($resultado['elegible']);
    }

    // ─── Helper fmt ────────────────────────────────────────────────────────

    public function test_fmt_formatea_montos_correctamente(): void
    {
        $this->assertSame('$1,000.00',     SimuladorPrecalificacion::fmt(1000));
        $this->assertSame('$1,234,567.89', SimuladorPrecalificacion::fmt(1234567.89));
        $this->assertSame('$0.00',         SimuladorPrecalificacion::fmt(0));
    }

    // ─── Constantes de negocio ─────────────────────────────────────────────

    public function test_constantes_tienen_valores_correctos_2024(): void
    {
        $this->assertSame(3300.54, SimuladorPrecalificacion::VSM_MENSUAL);
        $this->assertSame(954,     SimuladorPrecalificacion::TOPE_VSM);
        $this->assertSame(0.30,    SimuladorPrecalificacion::PORCENTAJE_PAGO);
        $this->assertSame(30,      SimuladorPrecalificacion::PLAZO_TRADICIONAL);
        $this->assertSame(20,      SimuladorPrecalificacion::PLAZO_PENSIONADOS);
        $this->assertSame(0.08,    SimuladorPrecalificacion::HONORARIOS_DEFAULT);
        $this->assertSame(430,     SimuladorPrecalificacion::TOPE_UMA_INFONAVIT);
        $this->assertEqualsWithDelta(1533474.60, SimuladorPrecalificacion::TOPE_INFONAVIT, 0.01);
    }
}
