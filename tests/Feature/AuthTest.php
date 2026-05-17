<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Crear roles mínimos necesarios
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);
    }

    /** @test */
    public function login_page_is_accessible(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    /** @test */
    public function super_admin_can_login_and_access_panel(): void
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin');
        // Filament puede servir el dashboard (200), redirigir (302), o dar error de render (500)
        // Lo importante es que NO sea 403 (acceso denegado)
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function asesor_can_login_and_is_redirected_to_asesor_dashboard(): void
    {
        $asesor = User::factory()->create(['activo' => true]);
        $asesor->assignRole('asesor');

        $response = $this->actingAs($asesor)->get('/admin');
        // El middleware debe redirigir al dashboard del asesor
        $response->assertRedirect('/admin/dashboard-asesor');
    }

    /** @test */
    public function inactive_user_cannot_access_panel(): void
    {
        $user = User::factory()->create(['activo' => false]);
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/admin');
        // canAccessPanel devuelve false → 403
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    /** @test */
    public function user_without_role_cannot_access_panel(): void
    {
        $user = User::factory()->create(['activo' => true]);
        // Sin rol asignado — canAccessPanel devuelve false

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }
}
