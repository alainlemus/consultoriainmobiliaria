<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Models\Contacto;
use App\Models\User;
use App\Notifications\AcreditadoRegistradoConCodigo;
use App\Notifications\ConflictoAsesorProspecto;
use App\Notifications\ProspectoSinAtender;
use App\Services\AsignacionAsesorQr;
use App\Support\MetricasQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Huecos del flujo QR → acreditado → prospecto:
 *  1. ligar el código de asesor después de registrarse
 *  2. avisar al asesor cuando alguien se registra con su código
 *  3. teléfono del asesor en el payload del acreditado
 *  4. conflicto de asesor marcado, notificado y resoluble
 *  5. tiempo de respuesta y alerta de prospectos sin atender
 */
class HuecosQrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $juan;
    private User $ana;
    private CodigoQr $qrJuan;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake(); Mail::fake(); Http::fake();

        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        foreach (['ViewAny:Contacto', 'View:Contacto', 'Update:Contacto'] as $p) {
            $this->admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($p, 'web'));
        }
        $this->juan = User::factory()->create(['activo' => true, 'name' => 'Juan Pérez', 'telefono' => '7711112222']);
        $this->ana  = User::factory()->create(['activo' => true, 'name' => 'Ana López']);
        $this->juan->assignRole('asesor');
        $this->ana->assignRole('asesor');

        $this->qrJuan = CodigoQr::create(['asesor_id' => $this->juan->id, 'codigo' => 'JUAN01', 'medio' => 'todos']);
    }

    private function acreditado(array $extra = []): Acreditado
    {
        return Acreditado::create(array_merge([
            'name' => 'María Hernández', 'email' => 'maria@example.com', 'password' => 'secreto123', 'telefono' => '7719998888',
        ], $extra));
    }

    // ── 1. Ligar el código después ────────────────────────────────────────────

    public function test_el_acreditado_sin_asesor_liga_su_codigo_desde_la_app(): void
    {
        $acr = $this->acreditado();

        $this->actingAs($acr, 'sanctum')
            ->putJson('/api/v1/acreditado/auth/codigo-asesor', ['codigo_asesor' => ' juan-01 '])
            ->assertOk()
            ->assertJsonPath('acreditado.asesor.nombre', 'Juan Pérez')
            ->assertJsonPath('acreditado.asesor.telefono', '7711112222');

        $this->assertDatabaseHas('acreditados', ['id' => $acr->id, 'asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->qrJuan->id, 'asesor_origen' => 'codigo_manual']);
        Notification::assertSentTo($this->juan, AcreditadoRegistradoConCodigo::class);
    }

    public function test_quien_ya_tiene_asesor_no_lo_cambia_desde_la_app(): void
    {
        $acr = $this->acreditado(['asesor_id' => $this->ana->id]);

        $this->actingAs($acr, 'sanctum')
            ->putJson('/api/v1/acreditado/auth/codigo-asesor', ['codigo_asesor' => 'JUAN01'])
            ->assertStatus(422);

        $this->assertSame($this->ana->id, $acr->fresh()->asesor_id);
        Notification::assertNothingSent();
    }

    public function test_codigo_invalido_al_ligar_despues_da_422(): void
    {
        $this->actingAs($this->acreditado(), 'sanctum')
            ->putJson('/api/v1/acreditado/auth/codigo-asesor', ['codigo_asesor' => 'NOEXISTE'])
            ->assertStatus(422)->assertJsonValidationErrors('codigo_asesor');
    }

    public function test_al_ligar_despues_su_prospecto_sin_asesor_tambien_se_asigna(): void
    {
        $contacto = Contacto::create(['nombre' => 'María', 'telefono' => '7719998888', 'servicio' => 'FOVISSSTE', 'origen' => 'app_acreditado', 'estado_prospecto' => 'nuevo']);
        $acr      = $this->acreditado(['contacto_id' => $contacto->id]);

        $this->actingAs($acr, 'sanctum')
            ->putJson('/api/v1/acreditado/auth/codigo-asesor', ['codigo_asesor' => 'JUAN01'])
            ->assertOk();

        $this->assertSame($this->juan->id, $contacto->fresh()->asesor_id);
        $this->assertSame($this->qrJuan->id, $contacto->fresh()->codigo_qr_id);
    }

    // ── 2. Aviso al asesor al registrarse ────────────────────────────────────

    public function test_registrarse_con_codigo_avisa_al_asesor_aunque_no_pida_asesoria(): void
    {
        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name' => 'Pedro', 'email' => 'pedro@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'telefono' => '7710001111', 'codigo_asesor' => 'JUAN01', 'asesor_origen' => 'referrer',
        ])->assertCreated()->assertJsonPath('acreditado.asesor.telefono', '7711112222');

        Notification::assertSentTo($this->juan, AcreditadoRegistradoConCodigo::class, function ($n) {
            return $n->acreditado->email === 'pedro@example.com'
                && $n->pushDataPublic()['screen'] === 'registros_qr';
        });
        $this->assertDatabaseHas('acreditados', ['email' => 'pedro@example.com', 'asesor_origen' => 'referrer']);
    }

    public function test_registrarse_sin_codigo_no_avisa_a_nadie(): void
    {
        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name' => 'Sin Código', 'email' => 'sin@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        ])->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_el_asesor_ve_sus_registrados_con_el_paso_en_que_van(): void
    {
        $soloRegistrado = $this->acreditado(['asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->qrJuan->id]);
        $contacto = Contacto::withoutEvents(fn () => Contacto::create(['nombre' => 'Luis', 'telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'qr', 'estado_prospecto' => 'contactado', 'asesor_id' => $this->juan->id]));
        $conSolicitud = $this->acreditado(['email' => 'luis@example.com', 'name' => 'Luis', 'asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->qrJuan->id, 'contacto_id' => $contacto->id]);
        $this->acreditado(['email' => 'deana@example.com', 'asesor_id' => $this->ana->id]);   // de otro asesor

        $res = $this->actingAs($this->juan, 'sanctum')->getJson('/api/v1/acreditados-qr')->assertOk();

        $res->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.sin_solicitud', 1)
            ->assertJsonPath('meta.con_solicitud', 1);
        $pasos = collect($res->json('data'))->pluck('paso', 'id');
        $this->assertSame('sin_solicitud', $pasos[$soloRegistrado->id]);
        $this->assertSame('solicitud', $pasos[$conSolicitud->id]);

        // El super_admin ve todos y puede filtrar por asesor
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/acreditados-qr')->assertJsonPath('meta.total', 3);
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/acreditados-qr?asesor_id=' . $this->ana->id)->assertJsonPath('meta.total', 1);
    }

    public function test_el_gestor_solo_no_ve_los_registrados(): void
    {
        Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
        $gestor = User::factory()->create(['activo' => true]);
        $gestor->assignRole('gestor');

        $this->actingAs($gestor, 'sanctum')->getJson('/api/v1/acreditados-qr')->assertForbidden();
    }

    // ── 4. Conflicto de asesor ────────────────────────────────────────────────

    public function test_la_solicitud_de_un_prospecto_con_otro_asesor_marca_conflicto_y_avisa_a_admins(): void
    {
        $previo = Contacto::withoutEvents(fn () => Contacto::create([
            'nombre' => 'María', 'telefono' => '7719998888', 'servicio' => 'FOVISSSTE', 'origen' => 'campo',
            'estado_prospecto' => 'contactado', 'asesor_id' => $this->ana->id,
        ]));
        $acr = $this->acreditado(['asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->qrJuan->id]);

        $this->actingAs($acr, 'sanctum')->postJson('/api/v1/acreditado/solicitudes', [])->assertCreated();

        $previo->refresh();
        $this->assertSame($this->ana->id, $previo->asesor_id, 'no se pisa');
        $this->assertSame($this->juan->id, $previo->asesor_conflicto_id);
        $this->assertNotNull($previo->conflicto_detectado_at);
        $this->assertStringContainsString('JUAN01', $previo->notas);
        Notification::assertSentTo($this->admin, ConflictoAsesorProspecto::class);
        Notification::assertNotSentTo($this->juan, ConflictoAsesorProspecto::class);
    }

    public function test_resolver_conflicto_mantener_o_cambiar(): void
    {
        $servicio = app(AsignacionAsesorQr::class);

        $contacto = Contacto::withoutEvents(fn () => Contacto::create([
            'nombre' => 'María', 'telefono' => '7719998888', 'servicio' => 'FOVISSSTE', 'origen' => 'campo', 'estado_prospecto' => 'contactado',
            'asesor_id' => $this->ana->id, 'asesor_conflicto_id' => $this->juan->id, 'conflicto_detectado_at' => now(),
        ]));
        $acr = $this->acreditado(['asesor_id' => $this->juan->id, 'contacto_id' => $contacto->id]);

        // Mantener: el prospecto sigue con Ana y la cuenta del acreditado se alinea con Ana
        $servicio->resolverConflicto($contacto, 'mantener');
        $this->assertSame($this->ana->id, $contacto->fresh()->asesor_id);
        $this->assertNull($contacto->fresh()->asesor_conflicto_id);
        $this->assertSame($this->ana->id, $acr->fresh()->asesor_id);

        // Cambiar: pasa a Juan
        $contacto->update(['asesor_conflicto_id' => $this->juan->id, 'conflicto_detectado_at' => now()]);
        $servicio->resolverConflicto($contacto, 'cambiar');
        $this->assertSame($this->juan->id, $contacto->fresh()->asesor_id);
        $this->assertNull($contacto->fresh()->asesor_conflicto_id);
    }

    public function test_el_panel_filtra_prospectos_con_conflicto_y_lo_resuelve(): void
    {
        $con = Contacto::withoutEvents(fn () => Contacto::create([
            'nombre' => 'Con Conflicto', 'telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'campo', 'estado_prospecto' => 'nuevo',
            'asesor_id' => $this->ana->id, 'asesor_conflicto_id' => $this->juan->id, 'conflicto_detectado_at' => now(),
        ]));
        $sin = Contacto::withoutEvents(fn () => Contacto::create(['nombre' => 'Sin Conflicto', 'telefono' => '2', 'servicio' => 'FOVISSSTE', 'origen' => 'campo', 'estado_prospecto' => 'nuevo', 'asesor_id' => $this->ana->id]));

        $this->actingAs($this->admin);

        \Livewire\Livewire::test(\App\Filament\Resources\ContactoResource\Pages\ListContactos::class)
            ->assertCanSeeTableRecords([$con, $sin])
            ->assertTableActionVisible('resolver_conflicto', $con)
            ->assertTableActionHidden('resolver_conflicto', $sin)
            ->filterTable('conflicto', true)
            ->assertCanSeeTableRecords([$con])
            ->assertCanNotSeeTableRecords([$sin])
            ->callTableAction('resolver_conflicto', $con, data: ['decision' => 'cambiar'])
            ->assertHasNoTableActionErrors();

        $this->assertSame($this->juan->id, $con->fresh()->asesor_id);
        $this->assertNull($con->fresh()->asesor_conflicto_id);
    }

    // ── 5. Tiempo de respuesta y sin atender ─────────────────────────────────

    public function test_contactado_at_se_guarda_al_salir_de_nuevo(): void
    {
        Carbon::setTestNow('2026-11-09 10:00:00');
        $c = Contacto::create(['nombre' => 'X', 'telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'qr', 'estado_prospecto' => 'nuevo', 'asesor_id' => $this->juan->id]);
        $this->assertNull($c->fresh()->contactado_at);

        Carbon::setTestNow('2026-11-09 13:30:00');
        $c->update(['estado_prospecto' => 'contactado']);
        $this->assertSame('2026-11-09 13:30:00', $c->fresh()->contactado_at->format('Y-m-d H:i:s'));

        // Una segunda transición no lo mueve
        Carbon::setTestNow('2026-11-10 09:00:00');
        $c->update(['estado_prospecto' => 'precalificado']);
        $this->assertSame('2026-11-09 13:30:00', $c->fresh()->contactado_at->format('Y-m-d H:i:s'));

        // Capturado en campo ya contactado: respuesta inmediata
        $d = Contacto::create(['nombre' => 'Y', 'telefono' => '2', 'servicio' => 'FOVISSSTE', 'origen' => 'campo', 'estado_prospecto' => 'contactado']);
        $this->assertNotNull($d->fresh()->contactado_at);
    }

    public function test_rendimiento_calcula_respuesta_media_y_sin_atender(): void
    {
        Carbon::setTestNow('2026-11-09 12:00:00');
        $base = ['telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'qr', 'asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->qrJuan->id];
        Contacto::withoutEvents(function () use ($base) {
            Contacto::forceCreate($base + ['nombre' => 'A', 'estado_prospecto' => 'contactado', 'created_at' => now()->subDays(3), 'contactado_at' => now()->subDays(3)->addHours(2)]);
            Contacto::forceCreate($base + ['nombre' => 'B', 'estado_prospecto' => 'contactado', 'created_at' => now()->subDays(2), 'contactado_at' => now()->subDays(2)->addHours(6)]);
            Contacto::forceCreate($base + ['nombre' => 'C', 'estado_prospecto' => 'nuevo',      'created_at' => now()->subDays(2)]);   // sin atender
            Contacto::forceCreate($base + ['nombre' => 'D', 'estado_prospecto' => 'nuevo',      'created_at' => now()->subHours(3)]);  // reciente, aún no cuenta
        });

        $t = (new MetricasQr(now()->subDays(29), now()))->totales();
        $this->assertSame(4.0, $t['respuesta_horas']);
        $this->assertSame(1, $t['sin_atender']);

        $juan = (new MetricasQr(now()->subDays(29), now()))->porAsesor()->first();
        $this->assertSame(4.0, $juan['respuesta_horas']);
        $this->assertSame(1, $juan['sin_atender']);

        $this->actingAs($this->admin)->get('/admin/rendimiento-qr')->assertOk()->assertSee('Respuesta media')->assertSee('Sin atender');
    }

    public function test_la_alerta_avisa_al_asesor_y_admins_y_no_repite_antes_de_dos_dias(): void
    {
        Carbon::setTestNow('2026-11-09 09:00:00');
        $viejo    = Contacto::withoutEvents(fn () => Contacto::forceCreate(['nombre' => 'Viejo', 'telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'qr', 'estado_prospecto' => 'nuevo', 'asesor_id' => $this->juan->id, 'created_at' => now()->subHours(30)]));
        Contacto::withoutEvents(fn () => Contacto::forceCreate(['nombre' => 'Reciente', 'telefono' => '2', 'servicio' => 'FOVISSSTE', 'origen' => 'qr', 'estado_prospecto' => 'nuevo', 'asesor_id' => $this->juan->id, 'created_at' => now()->subHours(5)]));
        Contacto::withoutEvents(fn () => Contacto::forceCreate(['nombre' => 'Sin asesor', 'telefono' => '3', 'servicio' => 'FOVISSSTE', 'origen' => 'sitio_web', 'estado_prospecto' => 'nuevo', 'created_at' => now()->subDays(3)]));

        $this->artisan('prospectos:alertar-sin-atender')->assertSuccessful();

        $alertasViejo = fn () => Notification::sent($this->juan, ProspectoSinAtender::class)
            ->filter(fn ($n) => $n->contacto->id === $viejo->id && $n->paraAsesor)->count();

        Notification::assertSentTo($this->admin, ProspectoSinAtender::class, fn ($n) => $n->contacto->id === $viejo->id && ! $n->paraAsesor);
        $this->assertSame(1, $alertasViejo());
        $this->assertNotNull($viejo->fresh()->alerta_sin_atender_at);
        // El reciente (5 h) y el que no tiene asesor no se alertan hoy
        $this->assertSame(1, Notification::sent($this->juan, ProspectoSinAtender::class)->count());

        // Al día siguiente no repite; a los 2 días sí
        Carbon::setTestNow('2026-11-10 09:00:00');
        $this->artisan('prospectos:alertar-sin-atender')->assertSuccessful();
        $this->assertSame(1, $alertasViejo());

        Carbon::setTestNow('2026-11-11 09:00:00');
        $this->artisan('prospectos:alertar-sin-atender')->assertSuccessful();
        $this->assertSame(2, $alertasViejo());

        // Ya contactado: deja de alertar
        $viejo->update(['estado_prospecto' => 'contactado']);
        Carbon::setTestNow('2026-11-13 09:00:00');
        $this->artisan('prospectos:alertar-sin-atender')->assertSuccessful();
        $this->assertSame(2, $alertasViejo());
    }
}
