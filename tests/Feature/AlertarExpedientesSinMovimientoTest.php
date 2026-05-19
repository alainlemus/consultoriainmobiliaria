<?php

namespace Tests\Feature;

use App\Models\Expediente;
use App\Models\EtapaTramite;
use App\Models\SeguimientoExpediente;
use App\Models\TipoTramite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlertarExpedientesSinMovimientoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private TipoTramite $tipoTramite;
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

        $this->tipoTramite = TipoTramite::create([
            'nombre'                => 'FOVISSSTE Tradicional',
            'slug'                  => 'fovissste-tradicional',
            'porcentaje_honorarios' => 8.0,
        ]);

        $this->etapa = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipoTramite->id,
            'nombre'          => 'Precalificación',
            'orden'           => 1,
        ]);
    }

    // ─── Helper ───────────────────────────────────────────────────────────

    private function crearExpediente(array $overrides = []): Expediente
    {
        return Expediente::create(array_merge([
            'asesor_id'        => $this->asesor->id,
            'tipo_tramite_id'  => $this->tipoTramite->id,
            'etapa_tramite_id' => $this->etapa->id,
            'estado'           => 'en_proceso',
            'acreditado_nombre'=> 'Cliente Test',
        ], $overrides));
    }

    // ─── Sin expedientes parados ───────────────────────────────────────────

    public function test_comando_no_envia_alertas_si_no_hay_expedientes_parados(): void
    {
        // Expediente con seguimiento reciente (hoy)
        $exp = $this->crearExpediente();
        SeguimientoExpediente::create([
            'expediente_id' => $exp->id,
            'usuario_id'    => $this->asesor->id,
            'tipo'          => 'nota',
            'descripcion'   => 'Seguimiento reciente',
        ]);

        $this->artisan('expedientes:alertar-sin-movimiento')
            ->expectsOutputToContain('Sin expedientes parados')
            ->assertSuccessful();
    }

    // ─── Detección de expedientes parados ─────────────────────────────────

    public function test_comando_detecta_expediente_sin_seguimiento(): void
    {
        // Expediente sin ningún seguimiento
        $this->crearExpediente(['acreditado_nombre' => 'Sin Seguimiento']);

        $this->artisan('expedientes:alertar-sin-movimiento')
            ->assertSuccessful();

        // El asesor debe tener una notificación en base de datos
        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->asesor->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_comando_detecta_expediente_con_seguimiento_antiguo(): void
    {
        // Simular que estamos 10 días en el pasado para crear el seguimiento
        Carbon::setTestNow(Carbon::now()->subDays(10));
        $exp = $this->crearExpediente();
        SeguimientoExpediente::create([
            'expediente_id' => $exp->id,
            'usuario_id'    => $this->asesor->id,
            'tipo'          => 'nota',
            'descripcion'   => 'Seguimiento viejo',
        ]);
        Carbon::setTestNow(); // restaurar tiempo real

        $this->artisan('expedientes:alertar-sin-movimiento')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->asesor->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_comando_no_alerta_si_seguimiento_es_reciente(): void
    {
        $exp = $this->crearExpediente();

        // Seguimiento de hace 3 días (dentro del umbral de 7)
        SeguimientoExpediente::create([
            'expediente_id' => $exp->id,
            'usuario_id'    => $this->asesor->id,
            'tipo'          => 'nota',
            'descripcion'   => 'Seguimiento reciente',
            'created_at'    => Carbon::now()->subDays(3),
            'updated_at'    => Carbon::now()->subDays(3),
        ]);

        $this->artisan('expedientes:alertar-sin-movimiento')
            ->expectsOutputToContain('Sin expedientes parados')
            ->assertSuccessful();

        // No debe haber notificaciones
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id'   => $this->asesor->id,
            'notifiable_type' => User::class,
        ]);
    }

    // ─── Expedientes cerrados no se alertan ───────────────────────────────

    public function test_expedientes_cerrados_no_generan_alerta(): void
    {
        $this->crearExpediente(['estado' => 'cerrado']);

        $this->artisan('expedientes:alertar-sin-movimiento')
            ->expectsOutputToContain('Sin expedientes parados')
            ->assertSuccessful();
    }

    public function test_expedientes_cancelados_no_generan_alerta(): void
    {
        $this->crearExpediente(['estado' => 'cancelado']);

        $this->artisan('expedientes:alertar-sin-movimiento')
            ->expectsOutputToContain('Sin expedientes parados')
            ->assertSuccessful();
    }

    // ─── Opción --dias personalizable ─────────────────────────────────────

    public function test_umbral_personalizado_con_opcion_dias(): void
    {
        // Crear expediente con seguimiento de hace 5 días
        Carbon::setTestNow(Carbon::now()->subDays(5));
        $exp = $this->crearExpediente();
        SeguimientoExpediente::create([
            'expediente_id' => $exp->id,
            'usuario_id'    => $this->asesor->id,
            'tipo'          => 'nota',
            'descripcion'   => 'Seguimiento de hace 5 días',
        ]);
        Carbon::setTestNow(); // restaurar tiempo real

        // Con --dias=7 (umbral mayor) → seguimiento de 5 días es reciente → sin alerta
        $this->artisan('expedientes:alertar-sin-movimiento', ['--dias' => 7])
            ->expectsOutputToContain('Sin expedientes parados')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id'   => $this->asesor->id,
            'notifiable_type' => User::class,
        ]);

        // Con --dias=3 (umbral menor) → seguimiento de 5 días es viejo → alerta
        $this->artisan('expedientes:alertar-sin-movimiento', ['--dias' => 3])
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->asesor->id,
            'notifiable_type' => User::class,
        ]);
    }

    // ─── Notificación también va a super_admin ────────────────────────────

    public function test_super_admin_recibe_resumen_global(): void
    {
        // Expediente sin seguimiento → debe notificar al admin también
        $this->crearExpediente();

        $this->artisan('expedientes:alertar-sin-movimiento')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $this->admin->id,
            'notifiable_type' => User::class,
        ]);
    }
}
