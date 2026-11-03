<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Rol gestor (trámites en dependencias públicas): existe tras migrar, entra al
 * panel, aterriza en su propia página y no ve el resto del panel.
 */
class GestorTest extends TestCase
{
    use RefreshDatabase;

    private function crearGestor(array $extra = []): User
    {
        $u = User::factory()->create(array_merge(['activo' => true], $extra));
        $u->assignRole('gestor');

        return $u;
    }

    public function test_el_rol_gestor_existe_despues_de_migrar(): void
    {
        $this->assertTrue(Role::where('name', 'gestor')->where('guard_name', 'web')->exists());
    }

    public function test_el_gestor_entra_al_panel_y_aterriza_en_su_pagina(): void
    {
        $gestor = $this->crearGestor();

        $this->actingAs($gestor)->get('/admin')->assertRedirect('/admin/gestor');
        $this->actingAs($gestor)->get('/admin/gestor')->assertOk()->assertSee('Mis expedientes');
    }

    public function test_el_gestor_no_ve_el_resto_del_panel(): void
    {
        $gestor = $this->crearGestor();

        foreach (['/admin/expedientes', '/admin/contactos', '/admin/users', '/admin/dashboard-asesor', '/admin/ayuda-tutorial'] as $url) {
            $this->assertContains(
                $this->actingAs($gestor)->get($url)->getStatusCode(),
                [403, 404],
                "El gestor no debería poder abrir {$url}",
            );
        }
    }

    public function test_un_gestor_inactivo_no_entra(): void
    {
        $this->actingAs($this->crearGestor(['activo' => false]))->get('/admin/gestor')->assertForbidden();
    }

    public function test_otros_roles_no_ven_la_pagina_del_gestor(): void
    {
        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);
        $asesor = User::factory()->create(['activo' => true]);
        $asesor->assignRole('asesor');

        $this->actingAs($asesor)->get('/admin/gestor')->assertForbidden();
    }

    public function test_el_gestor_inicia_sesion_en_la_api_y_recibe_su_rol(): void
    {
        $gestor = $this->crearGestor(['email' => 'gestor@example.com', 'password' => bcrypt('secreto123')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'gestor@example.com', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('user.roles', ['gestor']);
    }

    public function test_el_gestor_no_puede_usar_la_api_del_asesor(): void
    {
        $gestor = $this->crearGestor();
        \Laravel\Sanctum\Sanctum::actingAs($gestor);

        $this->getJson('/api/v1/auth/me')->assertOk();
        $this->getJson('/api/v1/contactos')->assertForbidden();
        $this->postJson('/api/v1/contactos', ['nombre' => 'X'])->assertForbidden();
        $this->getJson('/api/v1/expedientes')->assertForbidden();
    }

    public function test_el_asesor_sigue_usando_su_api(): void
    {
        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);
        $asesor = User::factory()->create(['activo' => true]);
        $asesor->assignRole('asesor');
        \Laravel\Sanctum\Sanctum::actingAs($asesor);

        $this->getJson('/api/v1/contactos')->assertOk();
    }
}
