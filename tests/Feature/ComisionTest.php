<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ComisionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private Expediente $expediente;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        $permisos = ['view_any_comision', 'view_comision'];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $this->asesor->syncPermissions($permisos);

        $tipo = TipoTramite::create([
            'nombre' => 'INFONAVIT', 'slug' => 'infonavit', 'porcentaje_honorarios' => 5,
        ]);
        $etapa = EtapaTramite::create([
            'tipo_tramite_id' => $tipo->id, 'nombre' => 'Inicio', 'orden' => 1,
        ]);

        $this->expediente = Expediente::create([
            'tipo_tramite_id'   => $tipo->id,
            'etapa_tramite_id'  => $etapa->id,
            'asesor_id'         => $this->asesor->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Cliente Test',
            'honorarios_monto'  => 80000,
        ]);
    }

    // ── Modelo ────────────────────────────────────────────────────────────────

    /** @test */
    public function comision_se_puede_crear_manualmente(): void
    {
        $comision = Comision::create([
            'expediente_id'       => $this->expediente->id,
            'asesor_id'           => $this->asesor->id,
            'monto_base'          => 80000,
            'porcentaje_comision' => 0,
            'monto_comision'      => 10000,
            'estado'              => 'pendiente',
            'fecha_generacion'    => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('comisiones', [
            'id'     => $comision->id,
            'estado' => 'pendiente',
        ]);
    }

    /** @test */
    public function comision_pertenece_a_expediente_y_asesor(): void
    {
        $comision = Comision::create([
            'expediente_id'       => $this->expediente->id,
            'asesor_id'           => $this->asesor->id,
            'monto_base'          => 80000,
            'porcentaje_comision' => 0,
            'monto_comision'      => 10000,
            'estado'              => 'pendiente',
            'fecha_generacion'    => now()->toDateString(),
        ]);

        $this->assertEquals($this->expediente->id, $comision->expediente->id);
        $this->assertEquals($this->asesor->id, $comision->asesor->id);
    }

    /** @test */
    public function estados_validos_de_comision(): void
    {
        $estados = ['pendiente', 'aprobada', 'pagada', 'rechazada'];

        foreach ($estados as $estado) {
            $comision = Comision::create([
                'expediente_id'       => $this->expediente->id,
                'asesor_id'           => $this->asesor->id,
                'monto_base'          => 1000,
                'porcentaje_comision' => 0,
                'monto_comision'      => 500,
                'estado'              => $estado,
                'fecha_generacion'    => now()->toDateString(),
            ]);

            $this->assertEquals($estado, $comision->estado);
        }
    }

    // ── Permisos ──────────────────────────────────────────────────────────────

    /** @test */
    public function asesor_no_puede_crear_comisiones(): void
    {
        Permission::firstOrCreate(['name' => 'create_comision', 'guard_name' => 'web']);
        $this->assertFalse($this->asesor->can('create_comision'));
    }

    /** @test */
    public function asesor_no_puede_editar_comisiones(): void
    {
        Permission::firstOrCreate(['name' => 'update_comision', 'guard_name' => 'web']);
        $this->assertFalse($this->asesor->can('update_comision'));
    }

    /** @test */
    public function asesor_no_puede_eliminar_comisiones(): void
    {
        Permission::firstOrCreate(['name' => 'delete_comision', 'guard_name' => 'web']);
        $this->assertFalse($this->asesor->can('delete_comision'));
    }

    /** @test */
    public function asesor_puede_ver_comisiones(): void
    {
        $this->assertTrue($this->asesor->can('view_comision'));
        $this->assertTrue($this->asesor->can('view_any_comision'));
    }

    // ── Acceso HTTP ───────────────────────────────────────────────────────────

    /** @test */
    public function asesor_puede_listar_comisiones(): void
    {
        $response = $this->actingAs($this->asesor)->get('/admin/comisions');
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function asesor_no_puede_acceder_a_crear_comision(): void
    {
        // Filament Shield bloquea la ruta create — puede devolver 403, 302 o 404
        $response = $this->actingAs($this->asesor)->get('/admin/comisions/create');
        $this->assertContains($response->getStatusCode(), [403, 302, 404]);
    }
}
