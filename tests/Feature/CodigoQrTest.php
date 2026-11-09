<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Models\Configuracion;
use App\Models\Contacto;
use App\Models\EscaneoQr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * QR por asesor impreso en publicidad (fase 1):
 *  - /a/{codigo} cuenta el escaneo y manda a la tienda según el celular
 *  - la app valida el código y el registro liga al acreditado con el asesor
 *  - la solicitud de asesoría nace asignada a ese asesor con origen 'qr'
 */
class CodigoQrTest extends TestCase
{
    use RefreshDatabase;

    private User $asesor;
    private User $otroAsesor;
    private CodigoQr $qr;

    private const UA_ANDROID = 'Mozilla/5.0 (Linux; Android 13; SM-A135M) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';
    private const UA_IPHONE  = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->asesor     = User::factory()->create(['activo' => true, 'name' => 'Juan Pérez']);
        $this->otroAsesor = User::factory()->create(['activo' => true, 'name' => 'Ana López']);
        $this->asesor->assignRole('asesor');
        $this->otroAsesor->assignRole('asesor');

        $this->qr = CodigoQr::create([
            'asesor_id'   => $this->asesor->id,
            'codigo'      => 'K7M2PX',
            'medio'       => 'lona',
            'descripcion' => 'Lona plaza Huejutla',
        ]);

        Configuracion::set('app_store_url',  'https://apps.apple.com/mx/app/ci/id123');
        Configuracion::set('play_store_url', 'https://play.google.com/store/apps/details?id=com.consultoriainmobiliaria.app');
    }

    // ── Redirección: la liga cuenta el escaneo y manda al formulario ──────────

    public function test_android_cuenta_el_escaneo_y_va_al_formulario(): void
    {
        $this->withHeader('User-Agent', self::UA_ANDROID)->get('/a/K7M2PX')
            ->assertRedirect(route('qr.formulario', ['codigo' => 'K7M2PX']));

        $this->assertDatabaseCount('escaneos_qr', 1);
        $this->assertDatabaseHas('escaneos_qr', ['codigo_qr_id' => $this->qr->id, 'plataforma' => 'android']);
    }

    public function test_iphone_y_computadora_tambien_van_al_formulario(): void
    {
        $this->withHeader('User-Agent', self::UA_IPHONE)->get('/a/k7m2px')   // minúsculas: debe tolerarlas
            ->assertRedirect(route('qr.formulario', ['codigo' => 'K7M2PX']));
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh) Chrome/120')->get('/a/K7M2PX')
            ->assertRedirect(route('qr.formulario', ['codigo' => 'K7M2PX']));

        $this->assertDatabaseHas('escaneos_qr', ['plataforma' => 'ios']);
        $this->assertDatabaseHas('escaneos_qr', ['plataforma' => 'otro']);
    }

    public function test_codigo_pausado_o_inexistente_no_cuenta_y_va_a_descargar_app(): void
    {
        $this->qr->update(['activo' => false]);

        $this->withHeader('User-Agent', self::UA_ANDROID)->get('/a/K7M2PX')
            ->assertRedirect(route('descargar.app'));
        $this->get('/a/NOEXISTE')->assertRedirect(route('descargar.app'));
        $this->get('/qr/K7M2PX')->assertNotFound();

        $this->assertDatabaseCount('escaneos_qr', 0);
    }

    public function test_la_tienda_de_play_lleva_el_referrer_del_codigo(): void
    {
        $url = $this->qr->urlPlayStore();
        $this->assertStringStartsWith('https://play.google.com/store/apps/details?id=com.consultoriainmobiliaria.app&referrer=', $url);
        $this->assertStringContainsString(urlencode('codigo_asesor=K7M2PX'), $url);
        $this->assertSame('https://apps.apple.com/mx/app/ci/id123', CodigoQr::urlAppStore());

        Configuracion::set('play_store_url', 'https://google.com.mx');
        $this->assertNull($this->qr->fresh()->urlPlayStore());
    }

    // ── API pública: validar código ───────────────────────────────────────────

    public function test_la_app_valida_el_codigo_y_recibe_el_nombre_del_asesor(): void
    {
        $this->getJson('/api/v1/acreditado/codigo-asesor/k7m-2px')
            ->assertOk()
            ->assertJson(['codigo' => 'K7M2PX', 'asesor' => ['id' => $this->asesor->id, 'nombre' => 'Juan Pérez']]);

        $this->getJson('/api/v1/acreditado/codigo-asesor/ZZZZZZ')->assertNotFound();
    }

    // ── Registro ──────────────────────────────────────────────────────────────

    public function test_registro_con_codigo_liga_al_acreditado_con_el_asesor(): void
    {
        $res = $this->postJson('/api/v1/acreditado/auth/registro', [
            'name'                  => 'María Hernández',
            'email'                 => 'maria@example.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'codigo_asesor'         => ' k7m2px ',
        ]);

        $res->assertCreated()
            ->assertJsonPath('acreditado.asesor.nombre', 'Juan Pérez');

        $this->assertDatabaseHas('acreditados', [
            'email'         => 'maria@example.com',
            'asesor_id'     => $this->asesor->id,
            'codigo_qr_id'  => $this->qr->id,
            'asesor_origen' => 'codigo_manual',
        ]);
    }

    public function test_registro_con_codigo_invalido_avisa_y_no_crea_cuenta(): void
    {
        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name'                  => 'María Hernández',
            'email'                 => 'maria@example.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
            'codigo_asesor'         => 'NOEXISTE',
        ])->assertStatus(422)->assertJsonValidationErrors('codigo_asesor');

        $this->assertDatabaseMissing('acreditados', ['email' => 'maria@example.com']);
    }

    public function test_registro_sin_codigo_sigue_funcionando(): void
    {
        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name'                  => 'Pedro Sin Código',
            'email'                 => 'pedro@example.com',
            'password'              => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertCreated()->assertJsonPath('acreditado.asesor', null);
    }

    // ── Solicitud de asesoría ─────────────────────────────────────────────────

    private function acreditadoConQr(array $extra = []): Acreditado
    {
        return Acreditado::create(array_merge([
            'name'          => 'María Hernández',
            'email'         => 'maria@example.com',
            'password'      => 'secreto123',
            'telefono'      => '7711234567',
            'asesor_id'     => $this->asesor->id,
            'codigo_qr_id'  => $this->qr->id,
            'asesor_origen' => 'codigo_manual',
        ], $extra));
    }

    public function test_la_solicitud_crea_el_prospecto_asignado_al_asesor_del_qr(): void
    {
        Mail::fake(); Notification::fake(); Http::fake();

        $acreditado = $this->acreditadoConQr();

        $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/solicitudes', ['mensaje' => 'Quiero mi crédito'])
            ->assertCreated();

        $this->assertDatabaseHas('contactos', [
            'email'        => 'maria@example.com',
            'asesor_id'    => $this->asesor->id,
            'codigo_qr_id' => $this->qr->id,
            'origen'       => 'qr',
        ]);

        // El embudo del QR ya refleja la solicitud
        $this->assertSame(1, $this->qr->contactos()->count());
    }

    public function test_un_prospecto_existente_sin_asesor_se_asigna_al_del_qr(): void
    {
        Mail::fake(); Notification::fake(); Http::fake();

        $previo = Contacto::create([
            'nombre' => 'María H.', 'telefono' => '7711234567', 'email' => 'otro@example.com',
            'servicio' => 'FOVISSSTE', 'origen' => 'sitio_web', 'estado_prospecto' => 'nuevo',
        ]);
        $acreditado = $this->acreditadoConQr();

        $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/solicitudes', [])
            ->assertCreated();

        $previo->refresh();
        $this->assertSame($this->asesor->id, $previo->asesor_id);
        $this->assertSame($this->qr->id, $previo->codigo_qr_id);
        $this->assertSame('sitio_web', $previo->origen, 'el origen original no se pisa');
        $this->assertDatabaseCount('contactos', 1);
    }

    public function test_un_prospecto_que_ya_tenia_otro_asesor_no_se_pisa_y_queda_nota(): void
    {
        Mail::fake(); Notification::fake(); Http::fake();

        $previo = Contacto::create([
            'nombre' => 'María H.', 'telefono' => '7711234567',
            'servicio' => 'FOVISSSTE', 'origen' => 'campo', 'estado_prospecto' => 'contactado',
            'asesor_id' => $this->otroAsesor->id, 'notas' => 'Visita en campo.',
        ]);
        $acreditado = $this->acreditadoConQr();

        $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/solicitudes', [])
            ->assertCreated();

        $previo->refresh();
        $this->assertSame($this->otroAsesor->id, $previo->asesor_id);
        $this->assertNull($previo->codigo_qr_id);
        $this->assertStringContainsString('Visita en campo.', $previo->notas);
        $this->assertStringContainsString('K7M2PX', $previo->notas);
        $this->assertStringContainsString('Juan Pérez', $previo->notas);
    }

    public function test_acreditado_sin_qr_sigue_creando_prospecto_app_acreditado_sin_asesor(): void
    {
        Mail::fake(); Notification::fake(); Http::fake();

        $acreditado = $this->acreditadoConQr(['asesor_id' => null, 'codigo_qr_id' => null, 'asesor_origen' => null]);

        $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/solicitudes', [])
            ->assertCreated();

        $this->assertDatabaseHas('contactos', ['email' => 'maria@example.com', 'origen' => 'app_acreditado', 'asesor_id' => null]);
    }

    // ── Panel: descargas y acceso ─────────────────────────────────────────────

    public function test_descargas_del_qr_requieren_sesion_y_entregan_png_svg_pdf(): void
    {
        $this->get(route('qr.asesor.png', $this->qr))->assertRedirect();

        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('qr.asesor.png', $this->qr))
            ->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($admin)->get(route('qr.asesor.svg', $this->qr))
            ->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->actingAs($admin)->get(route('qr.asesor.pdf', $this->qr))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_la_pagina_del_panel_carga_para_admin_y_asesor(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get('/admin/codigo-qrs')->assertOk()->assertSee('K7M2PX');
        $this->actingAs($admin)->get('/admin/codigo-qrs/create')->assertOk();
        $this->actingAs($admin)->get("/admin/codigo-qrs/{$this->qr->id}/edit")->assertOk();
        $this->actingAs($this->asesor)->get('/admin/codigo-qrs')->assertOk()->assertSee('K7M2PX');
    }

    public function test_el_asesor_solo_ve_sus_codigos_en_el_panel(): void
    {
        CodigoQr::create(['asesor_id' => $this->otroAsesor->id, 'codigo' => 'ANA001', 'medio' => 'tarjeta']);

        $this->actingAs($this->asesor);
        $codigos = \App\Filament\Resources\CodigoQrResource::getEloquentQuery()->pluck('codigo')->all();

        $this->assertSame(['K7M2PX'], $codigos);
    }

    public function test_el_medio_por_defecto_es_todos_y_se_puede_separar_por_medio(): void
    {
        $this->assertSame('todos', array_key_first(CodigoQr::MEDIOS));
        $this->assertSame('Todos los medios', CodigoQr::MEDIOS['todos']);

        $qr = CodigoQr::create(['asesor_id' => $this->asesor->id, 'codigo' => 'TODO01', 'medio' => 'todos']);
        $this->assertSame('Todos los medios', $qr->medio_label);

        // El mismo asesor puede tener otro QR para medir un medio aparte (queda abierto a propósito)
        CodigoQr::create(['asesor_id' => $this->asesor->id, 'codigo' => 'LONA01', 'medio' => 'lona']);
        $this->assertSame(3, CodigoQr::where('asesor_id', $this->asesor->id)->count());
    }

    public function test_generar_codigo_es_legible_y_unico(): void
    {
        $codigo = CodigoQr::generarCodigo();

        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $codigo);
        $this->assertNotSame('K7M2PX', $codigo);
        $this->assertSame('K7M2PX', CodigoQr::normalizar(' k7m-2px '));
    }

    public function test_borrar_el_qr_conserva_el_asesor_del_acreditado_y_del_prospecto(): void
    {
        $acreditado = $this->acreditadoConQr();
        $contacto   = Contacto::withoutEvents(fn () => Contacto::create([
            'nombre' => 'X', 'telefono' => '1', 'servicio' => 'FOVISSSTE', 'origen' => 'qr',
            'asesor_id' => $this->asesor->id, 'codigo_qr_id' => $this->qr->id,
        ]));

        $this->qr->delete();

        $this->assertSame($this->asesor->id, $acreditado->fresh()->asesor_id);
        $this->assertNull($acreditado->fresh()->codigo_qr_id);
        $this->assertSame($this->asesor->id, $contacto->fresh()->asesor_id);
        $this->assertDatabaseCount('escaneos_qr', 0);
        $this->assertSame(0, EscaneoQr::count());
    }
}
