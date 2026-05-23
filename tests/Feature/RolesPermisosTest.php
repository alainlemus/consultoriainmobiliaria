<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesPermisosTest extends TestCase
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

        // Super admin necesita permisos explícitos (define_via_gate está desactivado)
        $permisosAdmin = [
            'ViewAny:Expediente', 'View:Expediente', 'Create:Expediente', 'Update:Expediente',
            'ViewAny:Contacto',   'View:Contacto',   'Create:Contacto',   'Update:Contacto',
            'ViewAny:Comision',   'View:Comision',   'Create:Comision',   'Update:Comision',
            'ViewAny:User',       'View:User',       'Create:User',       'Update:User',
        ];
        foreach ($permisosAdmin as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $this->admin->syncPermissions($permisosAdmin);

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        // Permisos mínimos del asesor (formato Shield v4: PascalCase con separador :)
        $permisos = [
            'View:DashboardAsesor', 'View:MiPerfil',
            'ViewAny:Contacto', 'View:Contacto', 'Create:Contacto', 'Update:Contacto',
            'ViewAny:Expediente', 'View:Expediente', 'Create:Expediente', 'Update:Expediente',
            'ViewAny:Comision', 'View:Comision',
        ];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $this->asesor->syncPermissions($permisos);
    }

    /** @test */
    public function asesor_no_tiene_permiso_delete_contacto(): void
    {
        $this->assertFalse($this->asesor->can('Delete:Contacto'));
        $this->assertFalse($this->asesor->can('DeleteAny:Contacto'));
    }

    /** @test */
    public function asesor_no_tiene_permiso_delete_expediente(): void
    {
        $this->assertFalse($this->asesor->can('Delete:Expediente'));
        $this->assertFalse($this->asesor->can('DeleteAny:Expediente'));
    }

    /** @test */
    public function asesor_no_puede_acceder_a_recursos_de_super_admin(): void
    {
        // Rutas del panel que son solo super_admin
        $rutasRestringidas = [
            '/admin/users',
            '/admin/tipo-tramites',
            '/admin/coberturas',
            '/admin/procesos',
            '/admin/servicios',
            '/admin/foto-clientes',
            '/admin/posts',
            '/admin/propiedades',
            '/admin/testimonios',
        ];

        foreach ($rutasRestringidas as $ruta) {
            $response = $this->actingAs($this->asesor)->get($ruta);
            $this->assertContains(
                $response->getStatusCode(),
                [403, 302, 404], // 403 directo, 302 redirigiendo fuera, o 404 si la ruta no existe para ese rol
                "Asesor no debería acceder a {$ruta}, obtuvo {$response->getStatusCode()}"
            );
        }
    }

    /** @test */
    public function asesor_puede_acceder_a_sus_propias_rutas(): void
    {
        $rutasPermitidas = [
            '/admin/contactos',
            '/admin/expedientes',
            '/admin/comisiones',
            '/admin/dashboard-asesor',
        ];

        foreach ($rutasPermitidas as $ruta) {
            $response = $this->actingAs($this->asesor)->get($ruta);
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Asesor debería poder acceder a {$ruta}"
            );
        }
    }

    /** @test */
    public function asesor_no_puede_editar_comisiones(): void
    {
        $this->assertFalse($this->asesor->can('Update:Comision'));
        $this->assertFalse($this->asesor->can('Create:Comision'));
        $this->assertFalse($this->asesor->can('Delete:Comision'));
    }

    /** @test */
    public function super_admin_puede_acceder_a_todos_los_recursos(): void
    {
        $rutas = [
            '/admin/users',
            '/admin/contactos',
            '/admin/expedientes',
            '/admin/comisiones',
        ];

        foreach ($rutas as $ruta) {
            $response = $this->actingAs($this->admin)->get($ruta);
            $this->assertNotEquals(
                403,
                $response->getStatusCode(),
                "Super admin debería poder acceder a {$ruta}"
            );
        }
    }

    /** @test */
    public function can_access_panel_solo_usuarios_activos(): void
    {
        $inactivo = User::factory()->create(['activo' => false]);
        $inactivo->assignRole('asesor');

        $panel = app(\Filament\Panel::class);
        $this->assertFalse($inactivo->canAccessPanel($panel));
        $this->assertTrue($this->asesor->canAccessPanel($panel));
        $this->assertTrue($this->admin->canAccessPanel($panel));
    }
}
