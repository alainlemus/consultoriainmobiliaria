<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\Cita;
use App\Models\Cobertura;
use App\Models\Contacto;
use App\Models\User;
use App\Notifications\CitaNotificacion;
use App\Services\CitaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Citas: oficinas y horario desde el panel, huecos libres, una sola agenda, confirmación del super admin, avisos y recordatorios. */
class CitasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private Acreditado $acreditado;
    private Contacto $contacto;
    private Cobertura $huejutla;
    private Cobertura $pachuca;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake(); Mail::fake(); Http::fake();
        // Lunes 10 de noviembre de 2026, 08:00 de México
        Carbon::setTestNow(Carbon::parse('2026-11-10 08:00', CitaService::TZ)->utc());   // en UTC: un «ahora» con otra zona haría que los casts de BD se lean en esa zona

        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->admin  = User::factory()->create(['activo' => true, 'name' => 'Admin']); $this->admin->assignRole('super_admin');
        $this->asesor = User::factory()->create(['activo' => true, 'name' => 'Juan Pérez']); $this->asesor->assignRole('asesor');

        $this->huejutla = Cobertura::create(['nombre' => 'Huejutla', 'descripcion' => 'Oficina', 'detalle' => 'Plaza Tecoluco', 'activo' => true, 'citas_activas' => true,
            'horario_citas' => ['dias' => [1, 2, 3, 4, 5], 'inicio' => '09:00', 'fin' => '13:00', 'duracion_min' => 60, 'duracion_domicilio_min' => 120, 'permite_domicilio' => true]]);
        $this->pachuca  = Cobertura::create(['nombre' => 'Pachuca', 'descripcion' => 'Oficina', 'detalle' => 'Centro', 'activo' => true, 'citas_activas' => true]);
        Cobertura::create(['nombre' => 'Veracruz', 'descripcion' => 'Sin oficina', 'activo' => true, 'citas_activas' => false]);

        $this->contacto   = Contacto::withoutEvents(fn () => Contacto::create(['nombre' => 'María', 'telefono' => '7719998888', 'servicio' => 'FOVISSSTE', 'origen' => 'qr', 'estado_prospecto' => 'nuevo', 'asesor_id' => $this->asesor->id]));
        $this->acreditado = Acreditado::create(['name' => 'María', 'email' => 'maria@example.com', 'password' => 'secreto123', 'telefono' => '7719998888', 'asesor_id' => $this->asesor->id, 'contacto_id' => $this->contacto->id]);
    }

    private function hora(string $h, string $dia = '2026-11-11'): string
    {
        return Carbon::parse("{$dia} {$h}", CitaService::TZ)->toIso8601String();
    }

    public function test_solo_se_listan_las_oficinas_que_reciben_citas_con_su_horario(): void
    {
        $res = $this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/citas/oficinas')->assertOk();
        $this->assertSame(['Huejutla', 'Pachuca'], collect($res->json('data'))->pluck('nombre')->all());
        $this->assertSame('13:00', $res->json('data.0.fin'));
        $this->assertSame('17:00', $res->json('data.1.fin'), 'Pachuca usa el horario por defecto');
    }

    public function test_los_huecos_salen_del_horario_y_marcan_los_ocupados_y_pasados(): void
    {
        // Mañana (martes) 09-13 con citas de 60 min: 4 huecos
        $res = $this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/citas/disponibilidad?cobertura_id=' . $this->huejutla->id . '&fecha=2026-11-11')->assertOk();
        $this->assertCount(4, $res->json('data'));
        $this->assertTrue(collect($res->json('data'))->every(fn ($h) => $h['disponible']));

        // Ocupar 10:00 en Pachuca: también bloquea 10:00 en Huejutla (una sola agenda)
        app(CitaService::class)->solicitar(['cobertura_id' => $this->pachuca->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('10:00'), 'nombre' => 'Otro', 'contacto_id' => $this->contacto->id], $this->admin);
        $huecos = collect($this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/citas/disponibilidad?cobertura_id=' . $this->huejutla->id . '&fecha=2026-11-11')->json('data'));
        $this->assertFalse($huecos->firstWhere('inicia', $this->hora('10:00'))['disponible']);
        $this->assertTrue($huecos->firstWhere('inicia', $this->hora('11:00'))['disponible']);

        // Domicilio dura 120 min: a las 09:00 cabe, a las 12:00 ya no
        $dom = collect($this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/citas/disponibilidad?cobertura_id=' . $this->huejutla->id . '&fecha=2026-11-11&tipo=domicilio')->json('data'));
        $this->assertCount(3, $dom);
        $this->assertFalse($dom->firstWhere('inicia', $this->hora('09:00'))['disponible'], 'choca con la de 10:00');

        // Domingo: sin huecos. Hoy a las 07:00 ya pasó.
        $this->assertSame([], $this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/citas/disponibilidad?cobertura_id=' . $this->huejutla->id . '&fecha=2026-11-15')->json('data'));
    }

    public function test_el_asesor_agenda_para_su_prospecto_y_avisa_al_super_admin(): void
    {
        $res = $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/citas', [
            'cobertura_id' => $this->huejutla->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('10:00'), 'contacto_id' => $this->contacto->id, 'notas' => 'Trae su INE',
        ])->assertCreated();

        $res->assertJsonPath('data.estado', 'solicitada')->assertJsonPath('data.nombre', 'María')->assertJsonPath('data.asesor.nombre', 'Juan Pérez');
        $this->assertDatabaseHas('citas', ['contacto_id' => $this->contacto->id, 'acreditado_id' => $this->acreditado->id, 'asesor_id' => $this->asesor->id, 'creada_por_tipo' => 'asesor', 'estado' => 'solicitada']);
        Notification::assertSentTo($this->admin, CitaNotificacion::class, fn ($n) => $n->tipo === 'solicitada');
        Notification::assertNotSentTo($this->acreditado, CitaNotificacion::class);

        // Mismo hueco otra vez: ocupado. Prospecto de otro asesor: prohibido.
        $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/citas', ['cobertura_id' => $this->huejutla->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('10:00'), 'contacto_id' => $this->contacto->id])->assertStatus(422);
        $ajeno = Contacto::withoutEvents(fn () => Contacto::create(['nombre' => 'Ajeno', 'telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'campo', 'estado_prospecto' => 'nuevo', 'asesor_id' => $this->admin->id]));
        $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/citas', ['cobertura_id' => $this->huejutla->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('11:00'), 'contacto_id' => $ajeno->id])->assertForbidden();
    }

    public function test_fuera_de_horario_dia_sin_atencion_o_pasado_se_rechaza(): void
    {
        $base = ['cobertura_id' => $this->huejutla->id, 'tipo' => 'oficina', 'contacto_id' => $this->contacto->id];
        $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/citas', array_merge($base, ['inicia_at' => $this->hora('13:00')]))->assertStatus(422);              // termina 14:00, cierra 13:00
        $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/citas', array_merge($base, ['inicia_at' => $this->hora('10:00', '2026-11-15')]))->assertStatus(422); // domingo
        $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/citas', array_merge($base, ['inicia_at' => $this->hora('07:00', '2026-11-10')]))->assertStatus(422); // ya pasó
        $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/citas', array_merge($base, ['tipo' => 'domicilio', 'inicia_at' => $this->hora('09:00')]))->assertStatus(422);  // domicilio sin dirección
    }

    public function test_el_acreditado_agenda_con_su_asesor_y_puede_cancelar(): void
    {
        $this->actingAs($this->acreditado, 'sanctum')->getJson('/api/v1/acreditado/citas/oficinas')->assertOk()->assertJsonCount(2, 'data');

        $res = $this->actingAs($this->acreditado, 'sanctum')->postJson('/api/v1/acreditado/citas', [
            'cobertura_id' => $this->huejutla->id, 'tipo' => 'domicilio', 'inicia_at' => $this->hora('09:00'), 'direccion' => 'Calle 5 #12, Huejutla',
        ])->assertCreated();
        $res->assertJsonPath('data.tipo', 'domicilio')->assertJsonPath('data.asesor.nombre', 'Juan Pérez')->assertJsonPath('data.creada_por', 'acreditado');
        Notification::assertSentTo($this->admin, CitaNotificacion::class);

        $id = $res->json('data.id');
        $this->actingAs($this->acreditado, 'sanctum')->getJson('/api/v1/acreditado/citas')->assertJsonCount(1, 'data');
        $this->actingAs($this->acreditado, 'sanctum')->putJson("/api/v1/acreditado/citas/{$id}/cancelar", ['motivo' => 'No puedo ese día'])->assertOk()->assertJsonPath('data.estado', 'cancelada');
        Notification::assertSentTo($this->asesor, CitaNotificacion::class, fn ($n) => $n->tipo === 'cancelada');
        Notification::assertSentTo($this->admin, CitaNotificacion::class, fn ($n) => $n->tipo === 'cancelada');
    }

    public function test_el_super_admin_confirma_reagenda_y_marca_asistencia(): void
    {
        $cita = app(CitaService::class)->solicitar(['cobertura_id' => $this->huejutla->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('10:00'), 'nombre' => 'María', 'contacto_id' => $this->contacto->id, 'acreditado_id' => $this->acreditado->id, 'asesor_id' => $this->asesor->id], $this->asesor);
        Notification::fake();

        $this->actingAs($this->asesor, 'sanctum')->putJson("/api/v1/citas/{$cita->id}/confirmar")->assertForbidden();

        $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/citas/{$cita->id}/confirmar")->assertOk()->assertJsonPath('data.estado', 'confirmada');
        Notification::assertSentTo($this->acreditado, CitaNotificacion::class, fn ($n) => $n->tipo === 'confirmada');
        Notification::assertSentTo($this->asesor, CitaNotificacion::class, fn ($n) => $n->tipo === 'confirmada');
        $this->assertSame($this->admin->id, $cita->fresh()->atiende_id);

        // Reagendar a un hueco libre; a uno ocupado no
        app(CitaService::class)->solicitar(['cobertura_id' => $this->pachuca->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('12:00'), 'nombre' => 'Otro', 'contacto_id' => $this->contacto->id], $this->admin);
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/citas/{$cita->id}/reagendar", ['inicia_at' => $this->hora('12:00')])->assertStatus(422);
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/citas/{$cita->id}/reagendar", ['inicia_at' => $this->hora('11:00')])->assertOk()->assertJsonPath('data.estado', 'confirmada');
        Notification::assertSentTo($this->acreditado, CitaNotificacion::class, fn ($n) => $n->tipo === 'reagendada');
        $this->assertSame('11:00', $cita->fresh()->inicia_at->setTimezone(CitaService::TZ)->format('H:i'));

        // Su agenda: ve todas; el asesor solo las suyas
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/citas?desde=2026-11-11&hasta=2026-11-11')->assertOk()->assertJsonPath('meta.total', 2);
        $this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/citas?desde=2026-11-11&hasta=2026-11-11')->assertOk()->assertJsonPath('meta.total', 1);

        // Ya pasó: marcar asistencia
        Carbon::setTestNow(Carbon::parse('2026-11-11 13:00', CitaService::TZ)->utc());   // en UTC: un «ahora» con otra zona haría que los casts de BD se lean en esa zona
        $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/citas/{$cita->id}/realizada", ['asistio' => true])->assertOk()->assertJsonPath('data.estado', 'realizada');
    }

    public function test_recordatorios_un_dia_antes_y_dos_horas_antes_una_sola_vez(): void
    {
        $cita = app(CitaService::class)->solicitar(['cobertura_id' => $this->huejutla->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('10:00'), 'nombre' => 'María', 'contacto_id' => $this->contacto->id, 'acreditado_id' => $this->acreditado->id, 'asesor_id' => $this->asesor->id], $this->asesor);
        app(CitaService::class)->confirmar($cita, $this->admin);
        Notification::fake();
        $recordatorios = fn () => Notification::sent($this->acreditado, CitaNotificacion::class)->filter(fn ($n) => $n->tipo === 'recordatorio')->count();

        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00', CitaService::TZ)->utc());   // en UTC: un «ahora» con otra zona haría que los casts de BD se lean en esa zona   // 24 h antes
        $this->artisan('citas:recordar')->assertSuccessful();
        $this->assertSame(1, $recordatorios());
        Notification::assertSentTo($this->admin, CitaNotificacion::class, fn ($n) => $n->tipo === 'recordatorio');
        $this->artisan('citas:recordar');
        $this->assertSame(1, $recordatorios(), 'no repite');

        Carbon::setTestNow(Carbon::parse('2026-11-11 08:00', CitaService::TZ)->utc());   // en UTC: un «ahora» con otra zona haría que los casts de BD se lean en esa zona   // 2 h antes
        $this->artisan('citas:recordar');
        $this->assertSame(2, $recordatorios());
        $this->artisan('citas:recordar');
        $this->assertSame(2, $recordatorios());
    }

    public function test_el_panel_lista_las_citas_para_el_admin(): void
    {
        foreach (['ViewAny:Cita', 'View:Cita', 'Update:Cita'] as $p) {
            $this->admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($p, 'web'));
        }
        app(CitaService::class)->solicitar(['cobertura_id' => $this->huejutla->id, 'tipo' => 'oficina', 'inicia_at' => $this->hora('10:00'), 'nombre' => 'María Cita', 'contacto_id' => $this->contacto->id], $this->asesor);

        $this->actingAs($this->admin)->get('/admin/citas')->assertOk()->assertSee('María Cita')->assertSee('Confirmar')
            ->assertSee('Noviembre 2026')   // calendario del mes actual
            ->assertSee('10:00 am');
    }
}
