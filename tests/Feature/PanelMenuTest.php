<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** "Configuración CRM" y "API Móvil" van al final del menú y empiezan cerrados. */
class PanelMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_configuracion_crm_y_api_movil_van_al_final_y_cerrados(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');

        $html = $this->actingAs($admin)->get('/admin/users')->assertOk()->getContent();

        $this->assertStringContainsString('_x_collapsedGroups', $html);

        // Etiquetas de grupo en el orden en que aparecen en el menú lateral
        $nav = substr($html, strpos($html, 'fi-sidebar-nav'));
        preg_match_all('/fi-sidebar-group-label[^>]*>\s*([^<]+?)\s*</u', $nav, $m);
        $grupos = array_map('trim', $m[1]);

        $this->assertSame(['Configuración CRM', 'API Móvil'], array_slice($grupos, -2), 'Grupos: ' . implode(' | ', $grupos));
        $this->assertLessThan(array_search('Configuración CRM', $grupos), array_search('CRM', $grupos));
    }
}
