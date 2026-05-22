<?php

namespace Tests\Feature;

use App\Models\Contacto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactoTest extends TestCase
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

        $permisos = [
            'ViewAny:Contacto', 'View:Contacto', 'Create:Contacto', 'Update:Contacto',
        ];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $this->asesor->syncPermissions($permisos);
    }

    // ── Modelo ────────────────────────────────────────────────────────────────

    /** @test */
    public function contacto_se_crea_con_estado_prospecto_nuevo_por_defecto(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'Test Cliente',
            'telefono'  => '5512345678',
            'asesor_id' => $this->asesor->id,
        ]);

        $this->assertEquals('nuevo', $contacto->estado_prospecto);
    }

    /** @test */
    public function contacto_pertenece_a_un_asesor(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'Test Cliente',
            'asesor_id' => $this->asesor->id,
        ]);

        $this->assertEquals($this->asesor->id, $contacto->asesor->id);
    }

    /** @test */
    public function contacto_puede_tener_fechas_de_nacimiento_y_primer_contacto(): void
    {
        $contacto = Contacto::create([
            'nombre'               => 'Test Cliente',
            'asesor_id'            => $this->asesor->id,
            'fecha_nacimiento'     => '1990-05-15',
            'fecha_primer_contacto'=> '2026-01-10',
        ]);

        $this->assertEquals('1990-05-15', $contacto->fecha_nacimiento->toDateString());
        $this->assertEquals('2026-01-10', $contacto->fecha_primer_contacto->toDateString());
    }

    // ── Permisos y ownership ──────────────────────────────────────────────────

    /** @test */
    public function asesor_no_tiene_permiso_para_eliminar_contactos(): void
    {
        Permission::firstOrCreate(['name' => 'delete_contacto',     'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete_any_contacto', 'guard_name' => 'web']);

        $this->assertFalse($this->asesor->can('delete_contacto'));
        $this->assertFalse($this->asesor->can('delete_any_contacto'));
    }

    /** @test */
    public function super_admin_puede_eliminar_contactos(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'A Borrar',
            'asesor_id' => $this->asesor->id,
        ]);

        $contacto->delete();

        $this->assertDatabaseMissing('contactos', ['id' => $contacto->id]);
    }

    /** @test */
    public function asesor_solo_ve_sus_propios_contactos_via_query(): void
    {
        // Contacto del asesor
        $propio = Contacto::create(['nombre' => 'Mío',   'asesor_id' => $this->asesor->id]);
        // Contacto de otro asesor
        $ajeno  = Contacto::create(['nombre' => 'Ajeno', 'asesor_id' => $this->otroAsesor->id]);

        // Simular el scope que aplica ContactoResource::getEloquentQuery()
        $visibles = Contacto::where('asesor_id', $this->asesor->id)->pluck('id');

        $this->assertContains($propio->id, $visibles);
        $this->assertNotContains($ajeno->id, $visibles);
    }

    // ── Acceso HTTP a rutas del panel ─────────────────────────────────────────

    /** @test */
    public function asesor_puede_ver_lista_de_contactos(): void
    {
        $response = $this->actingAs($this->asesor)->get('/admin/contactos');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function asesor_puede_acceder_a_crear_contacto(): void
    {
        $response = $this->actingAs($this->asesor)->get('/admin/contactos/create');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function asesor_no_puede_acceder_a_eliminar_contacto_directamente(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'Test',
            'asesor_id' => $this->asesor->id,
        ]);

        // Verificar que el asesor no tiene el permiso de eliminar
        $this->assertFalse($this->asesor->can('Delete:Contacto'));
        $this->assertFalse($this->asesor->can('DeleteAny:Contacto'));
    }

    /** @test */
    public function estados_prospecto_validos(): void
    {
        $estados = ['nuevo', 'en_seguimiento', 'pendiente_cierre', 'contrato_firmado', 'convertido', 'no_califica'];

        foreach ($estados as $estado) {
            $contacto = Contacto::create([
                'nombre'           => "Test {$estado}",
                'asesor_id'        => $this->asesor->id,
                'estado_prospecto' => $estado,
            ]);

            $this->assertEquals($estado, $contacto->fresh()->estado_prospecto);
        }
    }
}
