<?php

namespace Tests\Feature;

use App\Models\Cobertura;
use App\Models\TipoTramite;
use App\Models\EtapaTramite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminRecursosTest extends TestCase
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

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');
    }

    // ── TipoTramite ───────────────────────────────────────────────────────────

    /** @test */
    public function tipo_tramite_se_crea_correctamente(): void
    {
        $tipo = TipoTramite::create([
            'nombre'                => 'FOVISSSTE',
            'slug'                  => 'fovissste',
            'porcentaje_honorarios' => 5.50,
        ]);

        $this->assertDatabaseHas('tipo_tramites', [
            'nombre' => 'FOVISSSTE',
            'slug'   => 'fovissste',
        ]);
        $this->assertEquals(5.50, $tipo->porcentaje_honorarios);
    }

    /** @test */
    public function tipo_tramite_slug_es_unico(): void
    {
        TipoTramite::create(['nombre' => 'FOVISSSTE', 'slug' => 'fovissste']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        TipoTramite::create(['nombre' => 'FOVISSSTE 2', 'slug' => 'fovissste']);
    }

    /** @test */
    public function super_admin_puede_ver_tipo_tramites(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/tipo-tramites');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function asesor_no_puede_ver_tipo_tramites(): void
    {
        $response = $this->actingAs($this->asesor)->get('/admin/tipo-tramites');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ── EtapaTramite ──────────────────────────────────────────────────────────

    /** @test */
    public function etapa_tramite_pertenece_a_tipo_tramite(): void
    {
        $tipo = TipoTramite::create([
            'nombre' => 'INFONAVIT', 'slug' => 'infonavit', 'porcentaje_honorarios' => 4,
        ]);

        $etapa = EtapaTramite::create([
            'tipo_tramite_id' => $tipo->id,
            'nombre'          => 'Precalificación',
            'orden'           => 1,
            'color'           => 'blue',
        ]);

        $this->assertEquals($tipo->id, $etapa->tipoTramite->id);
        $this->assertEquals('blue', $etapa->color);
    }

    /** @test */
    public function super_admin_puede_ver_etapas_tramite(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/etapa-tramites');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // ── Cobertura ─────────────────────────────────────────────────────────────

    /** @test */
    public function cobertura_se_crea_con_campos_requeridos(): void
    {
        $cobertura = Cobertura::create([
            'nombre'      => 'Hidalgo',
            'descripcion' => 'Estado de Hidalgo y municipios',
            'detalle'     => 'Oficina principal en Pachuca',
            'activo'      => true,
            'orden'       => 1,
        ]);

        $this->assertDatabaseHas('coberturas', ['nombre' => 'Hidalgo']);
        $this->assertTrue($cobertura->activo);
    }

    /** @test */
    public function super_admin_puede_ver_coberturas(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/coberturas');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function asesor_no_puede_ver_coberturas(): void
    {
        $response = $this->actingAs($this->asesor)->get('/admin/coberturas');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ── UserResource ──────────────────────────────────────────────────────────

    /** @test */
    public function super_admin_puede_ver_usuarios(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function asesor_no_puede_ver_usuarios(): void
    {
        $response = $this->actingAs($this->asesor)->get('/admin/users');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    /** @test */
    public function super_admin_puede_crear_usuario(): void
    {
        $nuevoUser = User::factory()->create([
            'name'   => 'Nuevo Asesor',
            'email'  => 'nuevo@test.com',
            'activo' => true,
        ]);

        $nuevoUser->assignRole('asesor');

        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
        $this->assertTrue($nuevoUser->hasRole('asesor'));
    }

    // ── User model ────────────────────────────────────────────────────────────

    /** @test */
    public function user_fillable_incluye_telefono(): void
    {
        $user = User::factory()->create(['telefono' => '7711234567']);
        $this->assertEquals('7711234567', $user->telefono);
    }

    /** @test */
    public function user_activo_false_no_puede_acceder_al_panel(): void
    {
        $user = User::factory()->create(['activo' => false]);
        $user->assignRole('asesor');

        $panel = app(\Filament\Panel::class);
        $this->assertFalse($user->canAccessPanel($panel));
    }
}
