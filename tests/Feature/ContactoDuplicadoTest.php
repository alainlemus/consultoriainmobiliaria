<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactoResource\Pages\CreateContacto;
use App\Filament\Resources\ContactoResource\Pages\EditContacto;
use App\Models\Acreditado;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactoDuplicadoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private User $otroAsesor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        $this->otroAsesor = User::factory()->create(['activo' => true]);
        $this->otroAsesor->assignRole('asesor');

        foreach (['ViewAny:Contacto', 'View:Contacto', 'Create:Contacto', 'Update:Contacto'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $this->admin->syncPermissions(['ViewAny:Contacto', 'View:Contacto', 'Create:Contacto', 'Update:Contacto']);
    }

    // ── Modelo: Contacto::buscarDuplicado() ─────────────────────────────────

    public function test_busca_duplicado_por_telefono(): void
    {
        $existente = Contacto::create(['nombre' => 'Juan', 'telefono' => '5511112222']);

        $encontrado = Contacto::buscarDuplicado('5511112222', null);

        $this->assertNotNull($encontrado);
        $this->assertEquals($existente->id, $encontrado->id);
    }

    public function test_busca_duplicado_por_curp(): void
    {
        $existente = Contacto::create(['nombre' => 'Ana', 'curp' => 'LOHA850101HDFPLN02']);

        $encontrado = Contacto::buscarDuplicado(null, 'LOHA850101HDFPLN02');

        $this->assertNotNull($encontrado);
        $this->assertEquals($existente->id, $encontrado->id);
    }

    public function test_busca_duplicado_no_encuentra_nada_si_no_coincide(): void
    {
        Contacto::create(['nombre' => 'Otro', 'telefono' => '5599998888']);

        $this->assertNull(Contacto::buscarDuplicado('5511112222', null));
        $this->assertNull(Contacto::buscarDuplicado(null, 'LOHA850101HDFPLN02'));
        $this->assertNull(Contacto::buscarDuplicado(null, null));
    }

    public function test_busca_duplicado_excluye_el_id_indicado(): void
    {
        $contacto = Contacto::create(['nombre' => 'Yo mismo', 'telefono' => '5511112222']);

        $this->assertNull(Contacto::buscarDuplicado('5511112222', null, $contacto->id));
    }

    // ── Panel admin: bloquear duplicados al crear ───────────────────────────

    public function test_admin_no_puede_crear_prospecto_con_telefono_duplicado(): void
    {
        Contacto::create(['nombre' => 'Existente', 'telefono' => '5511112222', 'asesor_id' => $this->asesor->id]);

        $this->actingAs($this->admin);

        Livewire::test(CreateContacto::class)
            ->fillForm([
                'nombre'   => 'Nuevo Prospecto',
                'telefono' => '5511112222',
            ])
            ->call('create')
            ->assertHasFormErrors(['telefono']);

        $this->assertEquals(1, Contacto::where('telefono', '5511112222')->count());
    }

    public function test_admin_no_puede_crear_prospecto_con_curp_duplicada(): void
    {
        Contacto::create([
            'nombre'    => 'Existente',
            'telefono'  => '5500000000',
            'curp'      => 'LOHA850101HDFPLN02',
            'asesor_id' => $this->asesor->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(CreateContacto::class)
            ->fillForm([
                'nombre'   => 'Nuevo Prospecto',
                'telefono' => '5522223333',
                'curp'     => 'LOHA850101HDFPLN02',
            ])
            ->call('create')
            ->assertHasFormErrors(['curp']);

        $this->assertEquals(1, Contacto::where('curp', 'LOHA850101HDFPLN02')->count());
    }

    public function test_admin_puede_crear_prospecto_sin_duplicados(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateContacto::class)
            ->fillForm([
                'nombre'   => 'Prospecto Nuevo',
                'telefono' => '5544445555',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertEquals(1, Contacto::where('telefono', '5544445555')->count());
    }

    public function test_admin_puede_editar_un_prospecto_sin_chocar_consigo_mismo(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'Editable',
            'telefono'  => '5511112222',
            'asesor_id' => $this->asesor->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(EditContacto::class, ['record' => $contacto->getRouteKey()])
            ->fillForm([
                'nombre'   => 'Editable Actualizado',
                'telefono' => '5511112222',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ── Formulario público del sitio web ────────────────────────────────────

    public function test_formulario_publico_no_duplica_por_telefono(): void
    {
        Mail::fake();

        Contacto::create(['nombre' => 'Pedro', 'telefono' => '5533334444', 'servicio' => 'INFONAVIT']);

        $this->withSession(['captcha_resultado' => 7])
            ->post('/contacto', [
                'captcha'  => '7',
                'nombre'   => 'Pedro Ramirez',
                'telefono' => '5533334444',
                'email'    => 'pedro@example.com',
                'servicio' => 'FOVISSSTE',
                'mensaje'  => 'Quiero otra cotización',
            ]);

        $this->assertEquals(1, Contacto::where('telefono', '5533334444')->count());

        $contacto = Contacto::where('telefono', '5533334444')->first();
        $this->assertStringContainsString('Volvió a escribir', $contacto->notas);
    }

    public function test_formulario_publico_crea_normalmente_si_no_hay_duplicado(): void
    {
        Mail::fake();

        $this->withSession(['captcha_resultado' => 7])
            ->post('/contacto', [
                'captcha'  => '7',
                'nombre'   => 'Nuevo Cliente',
                'telefono' => '5566667777',
                'email'    => 'nuevo@example.com',
                'servicio' => 'INFONAVIT',
            ]);

        $this->assertEquals(1, Contacto::where('telefono', '5566667777')->count());
    }

    // ── API móvil (asesor) ───────────────────────────────────────────────────

    public function test_api_movil_actualiza_en_vez_de_duplicar(): void
    {
        $existente = Contacto::create([
            'nombre'    => 'Original',
            'telefono'  => '5511112222',
            'asesor_id' => $this->asesor->id,
        ]);

        $res = $this->actingAs($this->otroAsesor, 'sanctum')
            ->postJson('/api/v1/contactos', [
                'nombre'   => 'Actualizado Por Otro Asesor',
                'telefono' => '5511112222',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('duplicado', true)
            ->assertJsonPath('data.id', $existente->id)
            ->assertJsonPath('data.nombre', 'Actualizado Por Otro Asesor')
            // El asesor original no debe perder la propiedad del prospecto.
            ->assertJsonPath('data.asesor_id', $this->asesor->id);

        $this->assertEquals(1, Contacto::where('telefono', '5511112222')->count());
    }

    public function test_api_movil_crea_normalmente_si_no_hay_duplicado(): void
    {
        $res = $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/contactos', [
                'nombre'   => 'Prospecto Nuevo',
                'telefono' => '5599990000',
            ]);

        $res->assertStatus(201)->assertJsonPath('duplicado', false);
        $this->assertEquals(1, Contacto::where('telefono', '5599990000')->count());
    }

    // ── API acreditado (solicitud de asesoría) ──────────────────────────────

    public function test_solicitud_acreditado_no_duplica_y_preserva_pipeline(): void
    {
        Mail::fake();

        $contacto = Contacto::create([
            'nombre'           => 'Luis Test',
            'telefono'         => '5544445555',
            'asesor_id'        => $this->asesor->id,
            'estado_prospecto' => 'contactado',
        ]);

        $acreditado = Acreditado::create([
            'name'     => 'Luis Test',
            'email'    => 'luis@example.com',
            'password' => bcrypt('password'),
            'telefono' => '5544445555',
        ]);

        $res = $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/solicitudes', [
                'mensaje' => 'Quiero información',
            ]);

        $res->assertStatus(201);

        $this->assertEquals(1, Contacto::where('telefono', '5544445555')->count());

        $contacto->refresh();
        // No se debe resetear el avance que ya llevaba el prospecto.
        $this->assertEquals('contactado', $contacto->estado_prospecto);
        $this->assertEquals($this->asesor->id, $contacto->asesor_id);
    }

    public function test_solicitud_acreditado_crea_contacto_si_no_existe(): void
    {
        Mail::fake();

        $acreditado = Acreditado::create([
            'name'     => 'Nuevo Acreditado',
            'email'    => 'nuevo-acreditado@example.com',
            'password' => bcrypt('password'),
            'telefono' => '5588889999',
        ]);

        $res = $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/solicitudes', [
                'mensaje' => 'Primera solicitud',
            ]);

        $res->assertStatus(201);

        $contacto = Contacto::where('telefono', '5588889999')->first();
        $this->assertNotNull($contacto);
        $this->assertEquals('nuevo', $contacto->estado_prospecto);
        $this->assertEquals('app_acreditado', $contacto->origen);
    }
}
