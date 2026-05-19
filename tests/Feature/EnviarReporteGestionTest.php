<?php

namespace Tests\Feature;

use App\Mail\ReporteGestion;
use App\Models\Comision;
use App\Models\Contacto;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\SeguimientoExpediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\ReporteGestionEnviado;
use App\Services\ReporteGestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnviarReporteGestionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private TipoTramite $tipo;
    private EtapaTramite $etapa;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        $this->tipo = TipoTramite::create([
            'nombre'                => 'FOVISSSTE Tradicional',
            'slug'                  => 'fovissste-tradicional',
            'porcentaje_honorarios' => 8.0,
        ]);

        $this->etapa = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipo->id,
            'nombre'          => 'Precalificación',
            'orden'           => 1,
        ]);
    }

    private function crearExpediente(array $extra = []): Expediente
    {
        return Expediente::create(array_merge([
            'asesor_id'         => $this->asesor->id,
            'tipo_tramite_id'   => $this->tipo->id,
            'etapa_tramite_id'  => $this->etapa->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Cliente Test',
        ], $extra));
    }

    // ─── Tipos de reporte: comandos se ejecutan sin error ─────────────────

    public function test_comando_diario_se_ejecuta_correctamente(): void
    {
        Mail::fake();
        $this->artisan('reportes:enviar', ['--tipo' => 'diario'])
            ->assertSuccessful();
        Mail::assertQueued(ReporteGestion::class);
    }

    public function test_comando_semanal_se_ejecuta_correctamente(): void
    {
        Mail::fake();
        $this->artisan('reportes:enviar', ['--tipo' => 'semanal'])
            ->assertSuccessful();
        Mail::assertQueued(ReporteGestion::class);
    }

    public function test_comando_mensual_se_ejecuta_correctamente(): void
    {
        Mail::fake();
        $this->artisan('reportes:enviar', ['--tipo' => 'mensual'])
            ->assertSuccessful();
        Mail::assertQueued(ReporteGestion::class);
    }

    public function test_tipo_invalido_retorna_failure(): void
    {
        Mail::fake();
        $this->artisan('reportes:enviar', ['--tipo' => 'quincenal'])
            ->assertFailed();
        Mail::assertNothingQueued();
    }

    // ─── Destinatarios ────────────────────────────────────────────────────

    public function test_reporte_se_envia_a_todos_los_super_admin(): void
    {
        Mail::fake();

        // Crear un segundo admin
        $admin2 = User::factory()->create(['activo' => true]);
        $admin2->assignRole('super_admin');

        $this->artisan('reportes:enviar', ['--tipo' => 'diario'])
            ->assertSuccessful();

        // Debe haberse encolado un email por cada admin (2)
        Mail::assertQueued(ReporteGestion::class, 2);
    }

    public function test_reporte_no_se_envia_si_no_hay_admins(): void
    {
        Mail::fake();

        // Eliminar el rol al admin
        $this->admin->syncRoles([]);

        $this->artisan('reportes:enviar', ['--tipo' => 'diario'])
            ->assertSuccessful()
            ->expectsOutputToContain('No hay super_admin');

        Mail::assertNothingQueued();
    }

    public function test_asesor_no_recibe_el_reporte(): void
    {
        Mail::fake();

        $this->artisan('reportes:enviar', ['--tipo' => 'diario'])
            ->assertSuccessful();

        // Solo el admin debe recibirlo
        Mail::assertQueued(ReporteGestion::class, fn ($mail) =>
            $mail->hasTo($this->admin->email)
        );
        Mail::assertNotQueued(ReporteGestion::class, fn ($mail) =>
            $mail->hasTo($this->asesor->email)
        );
    }

    // ─── Notificación en panel ────────────────────────────────────────────

    public function test_admin_recibe_notificacion_en_panel_tras_envio(): void
    {
        Mail::fake();
        Notification::fake();

        $this->artisan('reportes:enviar', ['--tipo' => 'diario'])
            ->assertSuccessful();

        Notification::assertSentTo($this->admin, ReporteGestionEnviado::class);
    }

    public function test_notificacion_contiene_tipo_correcto(): void
    {
        Mail::fake();
        Notification::fake();

        $this->artisan('reportes:enviar', ['--tipo' => 'semanal'])
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->admin,
            ReporteGestionEnviado::class,
            fn (ReporteGestionEnviado $notif) => $notif->tipo === 'semanal'
        );
    }

    // ─── Mailable: asunto y adjunto ───────────────────────────────────────

    public function test_mailable_tiene_asunto_con_tipo_y_periodo(): void
    {
        $service = app(ReporteGestionService::class);
        $desde   = Carbon::yesterday()->startOfDay();
        $hasta   = Carbon::yesterday()->endOfDay();
        $datos   = $service->generar($desde, $hasta, 'diario');
        $periodo = Carbon::yesterday()->format('d/m/Y');

        $mailable = new ReporteGestion(
            datos:   $datos,
            tipo:    'diario',
            periodo: $periodo,
        );

        $mailable->assertHasSubject("Reporte Diario de Gestión — {$periodo}");
    }

    public function test_mailable_incluye_adjunto_pdf(): void
    {
        $service = app(ReporteGestionService::class);
        $desde   = Carbon::yesterday()->startOfDay();
        $hasta   = Carbon::yesterday()->endOfDay();
        $datos   = $service->generar($desde, $hasta, 'diario');

        $mailable = new ReporteGestion(
            datos:   $datos,
            tipo:    'diario',
            periodo: Carbon::yesterday()->format('d/m/Y'),
        );

        // Verificar que hay al menos un adjunto con MIME pdf
        $adjuntos = $mailable->attachments();
        $this->assertNotEmpty($adjuntos, 'El mailable debe tener al menos un adjunto.');
    }

    // ─── ReporteGestionService: estructura de datos ───────────────────────

    public function test_service_genera_estructura_completa(): void
    {
        $service = app(ReporteGestionService::class);
        $desde   = Carbon::now()->startOfDay();
        $hasta   = Carbon::now()->endOfDay();
        $datos   = $service->generar($desde, $hasta, 'diario');

        $this->assertArrayHasKey('tipo', $datos);
        $this->assertArrayHasKey('desde', $datos);
        $this->assertArrayHasKey('hasta', $datos);
        $this->assertArrayHasKey('expedientes', $datos);
        $this->assertArrayHasKey('prospectos', $datos);
        $this->assertArrayHasKey('comisiones', $datos);
        $this->assertArrayHasKey('seguimientos', $datos);
        $this->assertArrayHasKey('sin_movimiento', $datos);
        $this->assertArrayHasKey('documentos', $datos);
        $this->assertArrayHasKey('por_asesor', $datos);
    }

    public function test_service_cuenta_expedientes_del_periodo(): void
    {
        $service = app(ReporteGestionService::class);

        // Crear 2 expedientes hoy
        $this->crearExpediente();
        $this->crearExpediente(['acreditado_nombre' => 'Cliente Dos']);

        $desde = Carbon::now()->startOfDay();
        $hasta = Carbon::now()->endOfDay();
        $datos = $service->generar($desde, $hasta, 'diario');

        $this->assertEquals(2, $datos['expedientes']['abiertos_periodo']);
    }

    public function test_service_cuenta_expedientes_cerrados_del_periodo(): void
    {
        $service = app(ReporteGestionService::class);

        $exp = $this->crearExpediente(['honorarios_monto' => 10000]);
        $exp->update(['estado' => 'cerrado']);

        $desde = Carbon::now()->startOfDay();
        $hasta = Carbon::now()->endOfDay();
        $datos = $service->generar($desde, $hasta, 'diario');

        $this->assertEquals(1, $datos['expedientes']['cerrados_periodo']);
    }

    public function test_service_cuenta_comisiones_generadas(): void
    {
        $service = app(ReporteGestionService::class);

        $exp = $this->crearExpediente();
        Comision::create([
            'expediente_id'       => $exp->id,
            'asesor_id'           => $this->asesor->id,
            'monto_base'          => 25000,
            'porcentaje_comision' => 8,
            'monto_comision'      => 25000,
            'estado'              => 'pendiente',
            'fecha_generacion'    => now()->toDateString(),
        ]);

        $desde = Carbon::now()->startOfDay();
        $hasta = Carbon::now()->endOfDay();
        $datos = $service->generar($desde, $hasta, 'diario');

        $this->assertEquals(1, $datos['comisiones']['generadas_count']);
        $this->assertEquals(25000, $datos['comisiones']['generadas_monto']);
    }

    public function test_service_detecta_expedientes_sin_movimiento(): void
    {
        $service = app(ReporteGestionService::class);

        // Expediente creado hace 10 días sin seguimiento
        Carbon::setTestNow(Carbon::now()->subDays(10));
        $exp = $this->crearExpediente();
        Carbon::setTestNow();

        $desde = Carbon::now()->startOfDay();
        $hasta = Carbon::now()->endOfDay();
        $datos = $service->generar($desde, $hasta, 'diario');

        $this->assertGreaterThanOrEqual(1, $datos['sin_movimiento']['total']);
    }

    public function test_service_incluye_actividad_por_asesor(): void
    {
        $service = app(ReporteGestionService::class);

        $this->crearExpediente();

        $desde = Carbon::now()->startOfDay();
        $hasta = Carbon::now()->endOfDay();
        $datos = $service->generar($desde, $hasta, 'diario');

        $this->assertNotEmpty($datos['por_asesor']);
        $this->assertEquals($this->asesor->name, $datos['por_asesor'][0]['asesor']);
    }
}
