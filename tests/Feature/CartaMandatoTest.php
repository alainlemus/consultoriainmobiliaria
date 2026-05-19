<?php

namespace Tests\Feature;

use App\Models\Expediente;
use App\Models\EtapaTramite;
use App\Models\TipoTramite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartaMandatoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private User $asesor2;
    private Expediente $expedienteDeAsesor;
    private Expediente $expedienteDeOtroAsesor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        $this->asesor2 = User::factory()->create(['activo' => true]);
        $this->asesor2->assignRole('asesor');

        $tipo = TipoTramite::create([
            'nombre'                => 'FOVISSSTE Tradicional',
            'slug'                  => 'fovissste-tradicional',
            'porcentaje_honorarios' => 8.0,
        ]);

        $etapa = EtapaTramite::create([
            'tipo_tramite_id' => $tipo->id,
            'nombre'          => 'Precalificación',
            'orden'           => 1,
        ]);

        $this->expedienteDeAsesor = Expediente::create([
            'asesor_id'         => $this->asesor->id,
            'tipo_tramite_id'   => $tipo->id,
            'etapa_tramite_id'  => $etapa->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Cliente Uno',
        ]);

        $this->expedienteDeOtroAsesor = Expediente::create([
            'asesor_id'         => $this->asesor2->id,
            'tipo_tramite_id'   => $tipo->id,
            'etapa_tramite_id'  => $etapa->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Cliente Dos',
        ]);
    }

    private function url(Expediente $exp, bool $preview = true): string
    {
        return route('contratos.carta_mandato', ['expediente' => $exp->id, 'preview' => $preview ? '1' : '0']);
    }

    // ─── Invitado ──────────────────────────────────────────────────────────

    public function test_invitado_es_redirigido_al_login(): void
    {
        $response = $this->get($this->url($this->expedienteDeAsesor));
        $response->assertRedirectContains('login');
    }

    // ─── Super Admin ───────────────────────────────────────────────────────

    public function test_super_admin_puede_ver_cualquier_carta_mandato(): void
    {
        $response = $this->actingAs($this->admin)
            ->get($this->url($this->expedienteDeAsesor));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_super_admin_puede_ver_carta_mandato_de_otro_asesor(): void
    {
        $response = $this->actingAs($this->admin)
            ->get($this->url($this->expedienteDeOtroAsesor));

        $response->assertStatus(200);
    }

    // ─── Asesor — expediente propio ────────────────────────────────────────

    public function test_asesor_puede_ver_carta_mandato_de_su_expediente(): void
    {
        $response = $this->actingAs($this->asesor)
            ->get($this->url($this->expedienteDeAsesor));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    // ─── Asesor — expediente ajeno ─────────────────────────────────────────

    public function test_asesor_no_puede_ver_carta_mandato_de_expediente_ajeno(): void
    {
        $response = $this->actingAs($this->asesor)
            ->get($this->url($this->expedienteDeOtroAsesor));

        $response->assertStatus(403);
    }

    // ─── Expediente inexistente ────────────────────────────────────────────

    public function test_expediente_inexistente_retorna_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('contratos.carta_mandato', ['expediente' => 99999]));

        $response->assertStatus(404);
    }

    // ─── Download (sin preview) ────────────────────────────────────────────

    public function test_super_admin_puede_descargar_carta_mandato(): void
    {
        $response = $this->actingAs($this->admin)
            ->get($this->url($this->expedienteDeAsesor, preview: false));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }
}
