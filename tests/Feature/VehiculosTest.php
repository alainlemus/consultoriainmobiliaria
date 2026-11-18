<?php

namespace Tests\Feature;

use App\Models\RoutePoint;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoCarga;
use App\Models\VehiculoReserva;
use App\Models\VehiculoTipo;
use App\Notifications\VehiculoNotificacion;
use App\Services\VehiculoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Préstamo de vehículos: fila por orden de llegada, autorización del super admin, checklists con fotos, llaves y licencia. */
class VehiculosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $juan;

    private User $laura;

    private VehiculoTipo $moto;

    private Vehiculo $moto1;

    private Vehiculo $moto2;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
        // Martes 17 de noviembre de 2026, 08:00 de México
        Carbon::setTestNow(Carbon::parse('2026-11-17 08:00', VehiculoService::TZ)->utc());

        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create(['activo' => true, 'name' => 'Dueño']);
        $this->admin->assignRole('super_admin');
        $this->juan = User::factory()->create(['activo' => true, 'name' => 'Juan']);
        $this->juan->assignRole('asesor');
        $this->laura = User::factory()->create(['activo' => true, 'name' => 'Laura']);
        $this->laura->assignRole('asesor');

        $this->moto = VehiculoTipo::where('nombre', 'Moto')->firstOrFail();   // la siembra la migración
        $this->moto1 = Vehiculo::create(['vehiculo_tipo_id' => $this->moto->id, 'nombre' => 'Moto 1', 'placa' => 'ABC-123', 'km_actual' => 12430]);
        $this->moto2 = Vehiculo::create(['vehiculo_tipo_id' => $this->moto->id, 'nombre' => 'Moto 2', 'placa' => 'XYZ-789', 'km_actual' => 800]);
    }

    private function h(string $hora, string $dia = '2026-11-18'): string
    {
        return Carbon::parse("{$dia} {$hora}", VehiculoService::TZ)->toIso8601String();
    }

    private function pedir(User $u, array $extra = [])
    {
        return $this->actingAs($u, 'sanctum')->postJson('/api/v1/vehiculos/reservas', array_merge([
            'vehiculo_id' => $this->moto1->id, 'todo_el_dia' => false,
            'inicio' => $this->h('10:00'), 'fin' => $this->h('13:00'), 'motivo' => 'Visita a prospecto',
        ], $extra));
    }

    private function fotosChecklist(bool $llaves = false): array
    {
        $f = [];
        foreach (['tablero', 'frente', 'atras', 'izquierda', 'derecha'] as $c) {
            $f[$c] = UploadedFile::fake()->image("{$c}.jpg", 400, 300);
        }
        if ($llaves) {
            $f['llaves'] = UploadedFile::fake()->image('llaves.jpg', 400, 300);
        }

        return $f;
    }

    private function autorizada(User $u): VehiculoReserva
    {
        $id = $this->pedir($u)->assertCreated()->json('data.id');

        return app(VehiculoService::class)->autorizar(VehiculoReserva::find($id), $this->admin);
    }

    public function test_el_asesor_ve_los_vehiculos_con_fotos_del_tipo_llaves_y_aviso_de_licencia(): void
    {
        $res = $this->actingAs($this->juan, 'sanctum')->getJson('/api/v1/vehiculos')->assertOk();

        $res->assertJsonPath('data.0.nombre', 'Moto 1')
            ->assertJsonPath('data.0.estado', 'disponible')
            ->assertJsonPath('data.0.llaves.en', 'oficina')
            ->assertJsonPath('data.0.tipo.fotos_requeridas.0.clave', 'frente')
            ->assertJsonPath('data.0.tipo.checklist.0', 'Casco')
            ->assertJsonPath('meta.licencia.vigente', false);
    }

    public function test_solicitar_queda_pendiente_y_avisa_al_super_admin(): void
    {
        $this->pedir($this->juan)->assertCreated()
            ->assertJsonPath('data.estado', 'solicitada')
            ->assertJsonPath('data.licencia_vigente', false)
            ->assertJsonPath('data.puede.cancelar', true)
            ->assertJsonPath('data.vehiculo.tipo.fotos_requeridas.0.clave', 'frente')
            ->assertJsonPath('data.vehiculo.tipo_nombre', 'Moto');

        $this->actingAs($this->juan, 'sanctum')->getJson("/api/v1/vehiculos/{$this->moto1->id}")->assertOk()->assertJsonPath('data.placa', 'ABC-123');

        Notification::assertSentTo($this->admin, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'solicitada' && str_contains($n->cuerpo, 'Sin licencia vigente'));
    }

    public function test_el_segundo_que_pide_el_mismo_horario_queda_en_espera_y_sube_cuando_se_libera(): void
    {
        $primera = $this->pedir($this->juan)->json('data.id');
        $this->pedir($this->laura, ['inicio' => $this->h('11:00'), 'fin' => $this->h('12:00')])->assertCreated()
            ->assertJsonPath('data.estado', 'en_espera')
            ->assertJsonPath('data.posicion_fila', 2);

        $segunda = VehiculoReserva::where('asesor_id', $this->laura->id)->first();
        $this->expectsValidation(fn () => app(VehiculoService::class)->autorizar($segunda, $this->admin));

        app(VehiculoService::class)->rechazar(VehiculoReserva::find($primera), $this->admin, 'Se priorizó otra visita');

        $this->assertSame(VehiculoReserva::SOLICITADA, $segunda->fresh()->estado);
        Notification::assertSentTo($this->laura, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'subio');
        Notification::assertSentTo($this->juan, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'rechazada' && str_contains($n->cuerpo, 'Se priorizó'));
    }

    public function test_el_que_este_libre_elige_otro_vehiculo_del_tipo(): void
    {
        $this->pedir($this->juan);
        $this->pedir($this->laura, ['vehiculo_id' => null, 'tipo_id' => $this->moto->id])->assertCreated()
            ->assertJsonPath('data.estado', 'solicitada')
            ->assertJsonPath('data.vehiculo.nombre', 'Moto 2');
    }

    public function test_todo_el_dia_con_resguardo_termina_a_la_manana_siguiente(): void
    {
        $res = $this->pedir($this->juan, ['todo_el_dia' => true, 'fecha' => '2026-11-18', 'inicio' => null, 'fin' => null, 'resguardo_casa' => true])->assertCreated();

        $this->assertSame($this->h('08:00'), $res->json('data.inicio'));
        $this->assertSame($this->h('09:00', '2026-11-19'), $res->json('data.fin'));
    }

    public function test_no_puede_tener_dos_reservas_en_el_mismo_horario(): void
    {
        $this->pedir($this->juan);
        $this->pedir($this->juan, ['vehiculo_id' => $this->moto2->id])->assertStatus(422)->assertJsonValidationErrors('inicio');
    }

    public function test_solo_los_asesores_solicitan(): void
    {
        $this->pedir($this->admin)->assertForbidden();
    }

    public function test_salida_exige_autorizacion_horario_fotos_y_foto_de_llaves_en_oficina(): void
    {
        $id = $this->pedir($this->juan)->json('data.id');
        $url = "/api/v1/vehiculos/reservas/{$id}/salida";
        $base = ['km' => 12440, 'gasolina' => 3];

        // Sin autorizar
        $this->actingAs($this->juan, 'sanctum')->post($url, $base + ['fotos' => $this->fotosChecklist(true)], ['Accept' => 'application/json'])->assertStatus(422);

        app(VehiculoService::class)->autorizar(VehiculoReserva::find($id), $this->admin);

        // Muy temprano (faltan más de 30 min)
        $this->actingAs($this->juan, 'sanctum')->post($url, $base + ['fotos' => $this->fotosChecklist(true)], ['Accept' => 'application/json'])->assertStatus(422);

        Carbon::setTestNow(Carbon::parse('2026-11-18 09:45', VehiculoService::TZ)->utc());

        // Sin foto de llaves (están en la oficina)
        $this->actingAs($this->juan, 'sanctum')->post($url, $base + ['fotos' => $this->fotosChecklist()], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('fotos.llaves');

        // Falta una foto del tipo
        $fotos = $this->fotosChecklist(true);
        unset($fotos['derecha']);
        $this->actingAs($this->juan, 'sanctum')->post($url, $base + ['fotos' => $fotos], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('fotos');

        // Km menor al último registrado
        $this->actingAs($this->juan, 'sanctum')->post($url, ['km' => 100, 'gasolina' => 3, 'fotos' => $this->fotosChecklist(true)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('km');

        $this->actingAs($this->juan, 'sanctum')->post($url, $base + ['fotos' => $this->fotosChecklist(true), 'checklist' => ['Casco']], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_uso')
            ->assertJsonPath('data.salida.km', 12440)
            ->assertJsonPath('data.salida.llaves_modo', 'oficina')
            ->assertJsonCount(6, 'data.salida.fotos')
            ->assertJsonPath('data.puede.entrega', true);

        $this->moto1->refresh();
        $this->assertSame('usuario', $this->moto1->llaves_en);
        $this->assertSame($this->juan->id, $this->moto1->llaves_user_id);
        $this->assertSame(3, $this->moto1->gasolina_actual);
    }

    public function test_entrega_en_oficina_cierra_la_reserva_y_calcula_km(): void
    {
        $r = $this->autorizada($this->juan);
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00', VehiculoService::TZ)->utc());
        app(VehiculoService::class)->registrarSalida($r, $this->juan, ['km' => 12440, 'gasolina' => 4], $this->fotosChecklist(true));

        RoutePoint::create(['user_id' => $this->juan->id, 'lat' => 21.14, 'lng' => -98.42, 'timestamp' => now()->addMinutes(10)]);
        RoutePoint::create(['user_id' => $this->juan->id, 'lat' => 21.15, 'lng' => -98.42, 'timestamp' => now()->addMinutes(20)]);
        Carbon::setTestNow(Carbon::parse('2026-11-18 12:30', VehiculoService::TZ)->utc());

        $url = "/api/v1/vehiculos/reservas/{$r->id}/entrega";
        $this->actingAs($this->juan, 'sanctum')->post($url, ['km' => 12400, 'gasolina' => 2, 'llaves_destino' => 'oficina', 'fotos' => $this->fotosChecklist(true)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('km');

        $this->actingAs($this->juan, 'sanctum')->post($url, ['km' => 12478, 'gasolina' => 2, 'llaves_destino' => 'oficina', 'fotos' => $this->fotosChecklist(true)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'entregada')
            ->assertJsonPath('data.km_recorridos', 38);

        $this->assertSame('oficina', $this->moto1->fresh()->llaves_en);
        $this->assertSame(12478, $this->moto1->fresh()->km_actual);
        $this->assertEqualsWithDelta(1.1, app(VehiculoService::class)->recorrido($r->fresh())['km_gps'], 0.1);
        Notification::assertSentTo($this->admin, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'entregada' && str_contains($n->cuerpo, '38 km'));
    }

    public function test_las_llaves_pasan_de_un_asesor_a_otro_con_codigo(): void
    {
        // Juan sale y entrega quedándose con las llaves
        $r = $this->autorizada($this->juan);
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00', VehiculoService::TZ)->utc());
        $svc = app(VehiculoService::class);
        $svc->registrarSalida($r, $this->juan, ['km' => 12440, 'gasolina' => 4], $this->fotosChecklist(true));
        $svc->registrarEntrega($r->fresh(), $this->juan, ['km' => 12460, 'gasolina' => 3, 'llaves_destino' => 'conmigo'], $this->fotosChecklist());
        $this->assertSame($this->juan->id, $this->moto1->fresh()->llaves_user_id);

        // Laura reserva por la tarde
        $id = $this->pedir($this->laura, ['inicio' => $this->h('14:00'), 'fin' => $this->h('16:00')])->json('data.id');
        $svc->autorizar(VehiculoReserva::find($id), $this->admin);
        Carbon::setTestNow(Carbon::parse('2026-11-18 13:50', VehiculoService::TZ)->utc());
        $url = "/api/v1/vehiculos/reservas/{$id}/salida";

        // Laura no puede generar código: no tiene las llaves
        $this->actingAs($this->laura, 'sanctum')->postJson("/api/v1/vehiculos/{$this->moto1->id}/llaves/codigo")->assertForbidden();

        $codigo = $this->actingAs($this->juan, 'sanctum')->postJson("/api/v1/vehiculos/{$this->moto1->id}/llaves/codigo")->assertOk()->json('data.codigo');
        $malo = $codigo === '0000' ? '1111' : '0000';

        $this->actingAs($this->laura, 'sanctum')->post($url, ['km' => 12460, 'gasolina' => 3, 'llaves_codigo' => $malo, 'fotos' => $this->fotosChecklist()], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('llaves_codigo');

        $this->actingAs($this->laura, 'sanctum')->post($url, ['km' => 12460, 'gasolina' => 3, 'llaves_codigo' => $codigo, 'fotos' => $this->fotosChecklist()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.salida.llaves_modo', 'codigo')
            ->assertJsonPath('data.salida.llaves_de.nombre', 'Juan');

        $this->assertSame($this->laura->id, $this->moto1->fresh()->llaves_user_id);
    }

    public function test_el_codigo_de_llaves_vence(): void
    {
        $this->moto1->update(['llaves_en' => 'usuario', 'llaves_user_id' => $this->juan->id]);
        $codigo = app(VehiculoService::class)->generarCodigo($this->moto1, $this->juan)['codigo'];
        Carbon::setTestNow(now()->addMinutes(VehiculoService::MIN_CODIGO + 1));

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/vehiculos/{$this->moto1->id}/llaves/recibir", ['codigo' => $codigo])
            ->assertStatus(422)->assertJsonValidationErrors('llaves_codigo');
    }

    public function test_el_dueno_recibe_las_llaves_con_codigo_y_luego_las_deja_en_oficina(): void
    {
        $this->moto1->update(['llaves_en' => 'usuario', 'llaves_user_id' => $this->juan->id]);
        $codigo = app(VehiculoService::class)->generarCodigo($this->moto1, $this->juan)['codigo'];

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/vehiculos/{$this->moto1->id}/llaves/recibir", ['codigo' => $codigo])
            ->assertOk()->assertJsonPath('data.llaves.soy_yo', true);

        $this->actingAs($this->admin, 'sanctum')->post("/api/v1/vehiculos/{$this->moto1->id}/llaves/oficina", ['fotos' => ['llaves' => UploadedFile::fake()->image('l.jpg')]], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.llaves.en', 'oficina');
    }

    public function test_un_vehiculo_en_taller_no_se_puede_pedir(): void
    {
        $this->moto1->update(['estado' => Vehiculo::TALLER]);
        $this->pedir($this->juan)->assertStatus(422)->assertJsonValidationErrors('vehiculo_id');
    }

    public function test_el_asesor_sube_su_licencia_y_queda_vigente(): void
    {
        $this->actingAs($this->juan, 'sanctum')->post('/api/v1/perfil/licencia', ['numero' => 'A123', 'vence' => '2028-05-01', 'foto' => UploadedFile::fake()->image('lic.jpg')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.vigente', true)->assertJsonPath('data.vence', '2028-05-01');

        $this->pedir($this->juan)->assertJsonPath('data.licencia_vigente', true);
    }

    public function test_un_asesor_no_ve_ni_cancela_reservas_de_otro(): void
    {
        $id = $this->pedir($this->juan)->json('data.id');
        $this->actingAs($this->laura, 'sanctum')->getJson("/api/v1/vehiculos/reservas/{$id}")->assertForbidden();
        $this->actingAs($this->laura, 'sanctum')->putJson("/api/v1/vehiculos/reservas/{$id}/cancelar")->assertForbidden();
        $this->actingAs($this->juan, 'sanctum')->putJson("/api/v1/vehiculos/reservas/{$id}/cancelar")->assertOk()->assertJsonPath('data.estado', 'cancelada');
    }

    public function test_el_panel_muestra_reservas_vehiculos_y_tipos_solo_al_super_admin(): void
    {
        $r = $this->autorizada($this->juan);
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00', VehiculoService::TZ)->utc());
        app(VehiculoService::class)->registrarSalida($r, $this->juan, ['km' => 12440, 'gasolina' => 3, 'notas_danos' => 'Rayón en salpicadera'], $this->fotosChecklist(true));
        RoutePoint::create(['user_id' => $this->juan->id, 'lat' => 21.14, 'lng' => -98.42, 'timestamp' => now()->addMinutes(5)]);
        RoutePoint::create(['user_id' => $this->juan->id, 'lat' => 21.15, 'lng' => -98.42, 'timestamp' => now()->addMinutes(9)]);
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:30', VehiculoService::TZ)->utc());
        $this->pedir($this->laura, ['inicio' => $this->h('15:00'), 'fin' => $this->h('16:00')]);

        $this->actingAs($this->admin, 'web')->get('/admin/vehiculo-reservas')->assertOk()
            ->assertSee('Juan')->assertSee('Laura')->assertSee('Autorizar')->assertSee('Sin licencia vigente');
        $this->actingAs($this->admin, 'web')->get("/admin/vehiculo-reservas/{$r->id}")->assertOk()
            ->assertSee('Rayón en salpicadera')->assertSee('Las tomó de la oficina')->assertSee('Recorrido');
        $this->actingAs($this->admin, 'web')->get('/admin/vehiculos')->assertOk()->assertSee('Moto 1')->assertSee('En uso · Juan');
        $this->actingAs($this->admin, 'web')->get('/admin/vehiculos/create')->assertOk();
        $this->actingAs($this->admin, 'web')->get('/admin/vehiculo-tipos')->assertOk()->assertSee('Coche');
        $this->actingAs($this->admin, 'web')->get("/admin/vehiculo-tipos/{$this->moto->id}/edit")->assertOk();

        $this->actingAs($this->juan, 'web')->get('/admin/vehiculo-reservas')->assertForbidden();
        $this->actingAs($this->juan, 'web')->get('/admin/vehiculos')->assertForbidden();
    }

    // ── Fase 2 ───────────────────────────────────────────────────────────────

    private function enCurso(User $u, array $extra = [], string $hora = '2026-11-18 10:00'): VehiculoReserva
    {
        $id = $this->pedir($u, $extra)->assertCreated()->json('data.id');
        $svc = app(VehiculoService::class);
        $svc->autorizar(VehiculoReserva::find($id), $this->admin);
        Carbon::setTestNow(Carbon::parse($hora, VehiculoService::TZ)->utc());

        return $svc->registrarSalida(VehiculoReserva::find($id), $u, ['km' => 12430, 'gasolina' => 4], $this->fotosChecklist(true));
    }

    public function test_dar_prioridad_desplaza_al_autorizado_y_le_ofrece_otro_vehiculo(): void
    {
        $svc = app(VehiculoService::class);
        $juan = $this->autorizada($this->juan);
        $lauraId = $this->pedir($this->laura, ['motivo' => 'Firma con cliente'])->assertJsonPath('data.estado', 'en_espera')->json('data.id');

        $this->actingAs($this->admin, 'web')->get('/admin/vehiculo-reservas')->assertOk()->assertSee('Dar prioridad')->assertSee('En espera #2');
        $svc->priorizar(VehiculoReserva::find($lauraId), $this->admin, 'Firma con cliente');

        $this->assertSame(VehiculoReserva::AUTORIZADA, VehiculoReserva::find($lauraId)->estado);
        $res = $this->actingAs($this->juan, 'sanctum')->getJson("/api/v1/vehiculos/reservas/{$juan->id}")->assertOk()
            ->assertJsonPath('data.estado', 'en_espera')
            ->assertJsonPath('data.posicion_fila', 2)
            ->assertJsonPath('data.desplazada.motivo', 'Firma con cliente')
            ->assertJsonPath('data.alternativas.0.nombre', 'Moto 2')
            ->assertJsonPath('data.puede.cambiar_vehiculo', true);
        Notification::assertSentTo($this->juan, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'desplazada' && str_contains($n->cuerpo, 'Moto 2'));

        // Toma la Moto 2: sigue autorizado porque ya lo estaba
        $this->actingAs($this->juan, 'sanctum')->putJson("/api/v1/vehiculos/reservas/{$juan->id}/cambiar-vehiculo", ['vehiculo_id' => $this->moto2->id])
            ->assertOk()->assertJsonPath('data.estado', 'autorizada')->assertJsonPath('data.vehiculo.nombre', 'Moto 2');
    }

    public function test_no_se_puede_priorizar_si_el_vehiculo_ya_salio(): void
    {
        $this->enCurso($this->juan, ['fin' => $this->h('18:00')]);
        $id = $this->pedir($this->laura, ['inicio' => $this->h('15:00'), 'fin' => $this->h('16:00')])->json('data.id');

        $this->expectsValidation(fn () => app(VehiculoService::class)->priorizar(VehiculoReserva::find($id), $this->admin, 'Urgente'));
    }

    public function test_guardar_en_casa_y_retomar_avisa_si_se_movio_de_noche(): void
    {
        $r = $this->enCurso($this->juan, ['todo_el_dia' => true, 'fecha' => '2026-11-18', 'inicio' => null, 'fin' => null, 'resguardo_casa' => true]);
        $base = "/api/v1/vehiculos/reservas/{$r->id}";
        $this->actingAs($this->juan, 'sanctum')->getJson($base)->assertJsonPath('data.puede.resguardo', true);

        Carbon::setTestNow(Carbon::parse('2026-11-18 19:30', VehiculoService::TZ)->utc());
        $this->actingAs($this->juan, 'sanctum')->post("{$base}/resguardo", ['km' => 12470, 'gasolina' => 2, 'fotos' => ['tablero' => UploadedFile::fake()->image('t.jpg')]], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('fotos');
        $this->actingAs($this->juan, 'sanctum')->post("{$base}/resguardo", ['km' => 12470, 'gasolina' => 2, 'lat' => 21.1, 'lng' => -98.4,
            'fotos' => ['tablero' => UploadedFile::fake()->image('t.jpg'), 'estacionada' => UploadedFile::fake()->image('e.jpg')]], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.estado', 'resguardo')->assertJsonPath('data.resguardo.km', 12470)
            ->assertJsonPath('data.puede.retomar', true)->assertJsonPath('data.puede.entrega', true);
        $this->actingAs($this->laura, 'sanctum')->getJson('/api/v1/vehiculos')->assertJsonPath('data.0.estado', 'en_uso');

        Carbon::setTestNow(Carbon::parse('2026-11-19 07:50', VehiculoService::TZ)->utc());
        $this->actingAs($this->juan, 'sanctum')->post("{$base}/retomar", ['km' => 12482, 'fotos' => ['tablero' => UploadedFile::fake()->image('t.jpg')]], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.estado', 'en_uso')->assertJsonPath('data.alertas', []);
        $this->assertStringContainsString('12 km durante la noche', $r->fresh()->alertas[0]);
    }

    public function test_carga_de_gasolina_con_reembolso_y_pago(): void
    {
        $r = $this->enCurso($this->juan);
        $url = "/api/v1/vehiculos/reservas/{$r->id}/cargas";
        $fotos = ['ticket' => UploadedFile::fake()->image('ticket.jpg'), 'tablero' => UploadedFile::fake()->image('t.jpg')];

        $this->actingAs($this->juan, 'sanctum')->post($url, ['km' => 12450, 'litros' => 10.2, 'monto' => 250, 'pago' => 'reembolso', 'fotos' => ['ticket' => $fotos['ticket']]], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->actingAs($this->juan, 'sanctum')->post($url, ['km' => 12450, 'litros' => 10.2, 'monto' => 250, 'pago' => 'reembolso', 'fotos' => $fotos], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.cargas.0.reembolso', 'pendiente')->assertJsonPath('data.cargas.0.monto', 250);
        Notification::assertSentTo($this->admin, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'carga' && str_contains($n->titulo, 'Reembolso'));

        $carga = VehiculoCarga::firstOrFail();
        app(VehiculoService::class)->marcarReembolsado($carga, $this->admin);
        $this->actingAs($this->juan, 'sanctum')->getJson('/api/v1/vehiculos/reembolsos')->assertOk()->assertJsonPath('data.0.reembolso', 'pagado');
        Notification::assertSentTo($this->juan, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'reembolso');

        $this->actingAs($this->admin, 'web')->get('/admin/vehiculo-cargas')->assertOk()->assertSee('Juan')->assertSee('Ver ticket');
    }

    public function test_recordatorio_de_salida_y_no_se_presento_libera_al_siguiente(): void
    {
        $svc = app(VehiculoService::class);
        $r = $this->autorizada($this->juan);
        $lauraId = $this->pedir($this->laura, ['inicio' => $this->h('11:00'), 'fin' => $this->h('12:00')])->json('data.id');

        Carbon::setTestNow(Carbon::parse('2026-11-18 09:40', VehiculoService::TZ)->utc());
        $this->assertSame(1, $svc->revisar()['recordatorios']);
        $this->assertSame(0, $svc->revisar()['recordatorios'], 'no repite');

        Carbon::setTestNow(Carbon::parse('2026-11-18 10:35', VehiculoService::TZ)->utc());
        $this->artisan('vehiculos:revisar')->assertSuccessful();

        $this->assertSame(VehiculoReserva::NO_PRESENTO, $r->fresh()->estado);
        $this->assertSame(VehiculoReserva::SOLICITADA, VehiculoReserva::find($lauraId)->estado);
        Notification::assertSentTo($this->juan, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'no_presento');
    }

    public function test_retraso_avisa_al_admin_y_al_siguiente(): void
    {
        $r = $this->enCurso($this->juan);
        $lauraId = $this->pedir($this->laura, ['inicio' => $this->h('14:00'), 'fin' => $this->h('15:00')])->json('data.id');
        app(VehiculoService::class)->autorizar(VehiculoReserva::find($lauraId), $this->admin);

        Carbon::setTestNow(Carbon::parse('2026-11-18 12:50', VehiculoService::TZ)->utc());
        app(VehiculoService::class)->revisar();
        Notification::assertSentTo($this->juan, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'recordatorio' && str_contains($n->titulo, 'termina'));

        Carbon::setTestNow(Carbon::parse('2026-11-18 13:40', VehiculoService::TZ)->utc());
        $this->assertSame(1, app(VehiculoService::class)->revisar()['retrasos']);
        $this->assertStringContainsString('No se entregó a tiempo', $r->fresh()->alertas[0]);
        Notification::assertSentTo($this->laura, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'retraso');
        Notification::assertSentTo($this->admin, VehiculoNotificacion::class, fn ($n) => $n->tipo === 'alerta');
    }

    public function test_alertas_por_km_sin_registro_gps_y_gasolina_en_reserva(): void
    {
        $id = $this->pedir($this->juan)->json('data.id');
        $svc = app(VehiculoService::class);
        $svc->autorizar(VehiculoReserva::find($id), $this->admin);
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00', VehiculoService::TZ)->utc());
        $r = $svc->registrarSalida(VehiculoReserva::find($id), $this->juan, ['km' => 12450, 'gasolina' => 4], $this->fotosChecklist(true));
        $this->assertStringContainsString('20 km sin registro', $r->alertas[0]);

        RoutePoint::create(['user_id' => $this->juan->id, 'lat' => 21.14, 'lng' => -98.42, 'timestamp' => now()->addMinutes(10)]);
        RoutePoint::create(['user_id' => $this->juan->id, 'lat' => 21.15, 'lng' => -98.42, 'timestamp' => now()->addMinutes(20)]);
        Carbon::setTestNow(Carbon::parse('2026-11-18 12:30', VehiculoService::TZ)->utc());
        $r = $svc->registrarEntrega($r, $this->juan, ['km' => 12490, 'gasolina' => 0, 'llaves_destino' => 'oficina'], $this->fotosChecklist(true));

        $this->assertCount(3, $r->alertas);
        $this->assertStringContainsString('GPS', $r->alertas[1]);
        $this->assertStringContainsString('reserva', $r->alertas[2]);
        $this->actingAs($this->admin, 'web')->get("/admin/vehiculo-reservas/{$r->id}")->assertOk()->assertSee('Alertas por revisar')->assertSee('20 km sin registro');
    }

    private function expectsValidation(callable $fn): void
    {
        try {
            $fn();
            $this->fail('Se esperaba un error de validación.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }
}
