<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Models\Configuracion;
use App\Models\Contacto;
use App\Models\User;
use App\Notifications\ConflictoAsesorProspecto;
use App\Notifications\ProspectoAsignado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Formulario al que manda el QR: captura el prospecto en el navegador, con el asesor puesto. */
class QrFormularioTest extends TestCase
{
    use RefreshDatabase;

    private User $juan;
    private User $ana;
    private CodigoQr $qr;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake(); Mail::fake(); Http::fake();

        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');
        $this->juan = User::factory()->create(['activo' => true, 'name' => 'Juan Pérez', 'telefono' => '7711112222']);
        $this->ana  = User::factory()->create(['activo' => true, 'name' => 'Ana López']);
        $this->juan->assignRole('asesor');
        $this->ana->assignRole('asesor');
        $this->qr = CodigoQr::create(['asesor_id' => $this->juan->id, 'codigo' => 'JUAN01', 'medio' => 'todos']);

        Configuracion::set('play_store_url', 'https://play.google.com/store/apps/details?id=com.consultoriainmobiliaria.app');
        Configuracion::set('app_store_url',  'https://apps.apple.com/mx/app/ci/id123');
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'María Hernández', 'telefono' => '771 999 8888', 'credito' => 'FOVISSSTE',
            'municipio' => 'Huejutla', 'privacidad' => '1',
        ], $extra);
    }

    public function test_el_formulario_carga_con_el_nombre_del_asesor(): void
    {
        $this->get('/qr/juan01')->assertOk()->assertSee('Juan Pérez')->assertSee('JUAN01')->assertSee('Quiero que me contacten');
    }

    public function test_enviar_crea_el_prospecto_asignado_al_asesor_y_le_avisa(): void
    {
        $res = $this->post('/qr/JUAN01', $this->datos(['email' => 'Maria@Example.com']));

        $res->assertRedirect(route('qr.gracias', ['codigo' => 'JUAN01']));
        $this->assertDatabaseHas('contactos', [
            'nombre' => 'María Hernández', 'telefono' => '7719998888', 'email' => 'maria@example.com',
            'servicio' => 'FOVISSSTE', 'municipio_uso_credito' => 'Huejutla', 'origen' => 'qr',
            'asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->qr->id, 'estado_prospecto' => 'nuevo',
        ]);
        Notification::assertSentTo($this->juan, ProspectoAsignado::class);

        // La página de gracias conoce al prospecto y ofrece WhatsApp, tiendas y crear cuenta
        $this->followingRedirects()->post('/qr/JUAN01', $this->datos())->assertOk();
        $html = $this->get('/qr/JUAN01/gracias')->assertOk()->getContent();
        $this->assertStringContainsString('María', $html);
        $this->assertStringContainsString('wa.me/527711112222', $html);
        $this->assertStringContainsString(urlencode('codigo_asesor=JUAN01'), $html);
        $this->assertStringContainsString('Crear mi contraseña', $html);
    }

    public function test_valida_telefono_de_10_digitos_y_privacidad(): void
    {
        $this->from('/qr/JUAN01')->post('/qr/JUAN01', $this->datos(['telefono' => '12345']))
            ->assertRedirect('/qr/JUAN01')->assertSessionHasErrors('telefono');
        $this->from('/qr/JUAN01')->post('/qr/JUAN01', $this->datos(['privacidad' => null]))
            ->assertSessionHasErrors('privacidad');
        $this->assertDatabaseCount('contactos', 0);
    }

    public function test_el_honeypot_no_crea_nada_pero_responde_igual(): void
    {
        $this->post('/qr/JUAN01', $this->datos(['sitio_web' => 'http://spam']))
            ->assertRedirect(route('qr.gracias', ['codigo' => 'JUAN01']));
        $this->assertDatabaseCount('contactos', 0);
    }

    public function test_telefono_repetido_reutiliza_el_prospecto_y_le_pone_asesor_si_no_tenia(): void
    {
        $previo = Contacto::create(['nombre' => 'María H.', 'telefono' => '7719998888', 'servicio' => 'FOVISSSTE', 'origen' => 'sitio_web', 'estado_prospecto' => 'nuevo']);

        $this->post('/qr/JUAN01', $this->datos())->assertRedirect();

        $this->assertDatabaseCount('contactos', 1);
        $previo->refresh();
        $this->assertSame($this->juan->id, $previo->asesor_id);
        $this->assertSame('sitio_web', $previo->origen, 'el origen original no se pisa');
    }

    public function test_telefono_repetido_con_otro_asesor_marca_conflicto(): void
    {
        $previo = Contacto::withoutEvents(fn () => Contacto::create(['nombre' => 'María H.', 'telefono' => '7719998888', 'servicio' => 'FOVISSSTE', 'origen' => 'campo', 'estado_prospecto' => 'contactado', 'asesor_id' => $this->ana->id]));

        $this->post('/qr/JUAN01', $this->datos())->assertRedirect();

        $previo->refresh();
        $this->assertSame($this->ana->id, $previo->asesor_id);
        $this->assertSame($this->juan->id, $previo->asesor_conflicto_id);
        Notification::assertSentTo(User::role('super_admin')->first(), ConflictoAsesorProspecto::class);
    }

    public function test_crear_cuenta_desde_gracias_queda_ligada_al_asesor_y_al_prospecto(): void
    {
        $this->post('/qr/JUAN01', $this->datos());
        $contacto = Contacto::first();

        $this->post('/qr/JUAN01/cuenta', ['email' => 'maria@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123'])
            ->assertRedirect(route('qr.gracias', ['codigo' => 'JUAN01']));

        $this->assertDatabaseHas('acreditados', [
            'email' => 'maria@example.com', 'name' => 'María Hernández', 'telefono' => '7719998888',
            'contacto_id' => $contacto->id, 'asesor_id' => $this->juan->id, 'codigo_qr_id' => $this->qr->id, 'asesor_origen' => 'formulario_web',
        ]);
        $this->assertSame('maria@example.com', $contacto->fresh()->email);
        $this->get('/qr/JUAN01/gracias')->assertOk()->assertSee('Tu cuenta quedó lista');

        // Y entra a la app con esa contraseña, ya con su asesor
        $this->postJson('/api/v1/acreditado/auth/login', ['email' => 'maria@example.com', 'password' => 'secreto123'])
            ->assertOk()->assertJsonPath('acreditado.asesor.nombre', 'Juan Pérez');
    }

    public function test_crear_cuenta_con_correo_ya_usado_avisa(): void
    {
        Acreditado::create(['name' => 'Otra', 'email' => 'maria@example.com', 'password' => 'secreto123']);
        $this->post('/qr/JUAN01', $this->datos());

        $this->from('/qr/JUAN01/gracias')
            ->post('/qr/JUAN01/cuenta', ['email' => 'maria@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123'])
            ->assertRedirect('/qr/JUAN01/gracias')->assertSessionHasErrors('email');
    }

    public function test_crear_cuenta_sin_haber_llenado_el_formulario_falla(): void
    {
        $this->post('/qr/JUAN01/cuenta', ['email' => 'x@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123'])
            ->assertStatus(419);
    }

    public function test_registrarse_en_la_app_sin_codigo_con_el_mismo_telefono_liga_al_asesor(): void
    {
        $this->post('/qr/JUAN01', $this->datos());
        $contacto = Contacto::first();

        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name' => 'María Hernández', 'email' => 'maria@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'telefono' => '7719998888',
        ])->assertCreated()->assertJsonPath('acreditado.asesor.nombre', 'Juan Pérez');

        $this->assertDatabaseHas('acreditados', ['email' => 'maria@example.com', 'asesor_id' => $this->juan->id, 'contacto_id' => $contacto->id, 'asesor_origen' => 'telefono']);
    }

    public function test_registrarse_sin_codigo_y_sin_prospecto_previo_queda_sin_asesor(): void
    {
        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name' => 'Nadie', 'email' => 'nadie@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'telefono' => '7700000000',
        ])->assertCreated()->assertJsonPath('acreditado.asesor', null);
    }
}
