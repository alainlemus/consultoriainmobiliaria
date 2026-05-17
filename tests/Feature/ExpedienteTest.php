<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Observers\ExpedienteObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpedienteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private TipoTramite $tipoTramite;
    private EtapaTramite $etapa;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        $this->tipoTramite = TipoTramite::create([
            'nombre'                => 'FOVISSSTE',
            'slug'                  => 'fovissste',
            'porcentaje_honorarios' => 5.00,
        ]);

        $this->etapa = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipoTramite->id,
            'nombre'          => 'Precalificación',
            'orden'           => 1,
        ]);

        // Registrar observer manualmente para tests
        Expediente::observe(ExpedienteObserver::class);
    }

    private function makeExpediente(array $overrides = []): Expediente
    {
        return Expediente::create(array_merge([
            'tipo_tramite_id'   => $this->tipoTramite->id,
            'etapa_tramite_id'  => $this->etapa->id,
            'asesor_id'         => $this->asesor->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Juan Prueba',
        ], $overrides));
    }

    // ── Folio ─────────────────────────────────────────────────────────────────

    /** @test */
    public function expediente_genera_folio_automaticamente(): void
    {
        $exp = $this->makeExpediente();

        $this->assertNotNull($exp->folio);
        $this->assertStringStartsWith('EXP-', $exp->folio);
        $this->assertStringContainsString((string) now()->year, $exp->folio);
    }

    /** @test */
    public function folio_es_secuencial_por_anio(): void
    {
        $exp1 = $this->makeExpediente();
        $exp2 = $this->makeExpediente();

        $this->assertNotEquals($exp1->folio, $exp2->folio);
        $this->assertEquals('EXP-' . now()->year . '-0001', $exp1->folio);
        $this->assertEquals('EXP-' . now()->year . '-0002', $exp2->folio);
    }

    /** @test */
    public function folio_no_se_sobreescribe_si_ya_existe(): void
    {
        $exp = $this->makeExpediente(['folio' => 'EXP-MANUAL-001']);
        $this->assertEquals('EXP-MANUAL-001', $exp->folio);
    }

    // ── Estado ────────────────────────────────────────────────────────────────

    /** @test */
    public function expediente_tiene_estado_en_proceso_por_defecto(): void
    {
        $exp = $this->makeExpediente();
        $this->assertEquals('en_proceso', $exp->estado);
    }

    /** @test */
    public function expediente_puede_cambiar_de_estado(): void
    {
        $exp = $this->makeExpediente();
        $exp->update(['estado' => 'aprobado']);
        $this->assertEquals('aprobado', $exp->fresh()->estado);
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    /** @test */
    public function expediente_pertenece_a_un_asesor(): void
    {
        $exp = $this->makeExpediente();
        $this->assertEquals($this->asesor->id, $exp->asesor->id);
    }

    /** @test */
    public function expediente_pertenece_a_un_tipo_tramite(): void
    {
        $exp = $this->makeExpediente();
        $this->assertEquals($this->tipoTramite->id, $exp->tipoTramite->id);
    }

    /** @test */
    public function expediente_usa_soft_deletes(): void
    {
        $exp = $this->makeExpediente();
        $id  = $exp->id;

        $exp->delete();

        $this->assertSoftDeleted('expedientes', ['id' => $id]);
        $this->assertNotNull(Expediente::withTrashed()->find($id));
    }

    // ── Observer: comisión al cerrar ──────────────────────────────────────────

    /** @test */
    public function al_cerrar_expediente_se_genera_comision(): void
    {
        $exp = $this->makeExpediente(['honorarios_monto' => 50000]);

        $exp->update(['estado' => 'cerrado']);

        $this->assertDatabaseHas('comisiones', [
            'expediente_id' => $exp->id,
            'asesor_id'     => $this->asesor->id,
            'estado'        => 'pendiente',
        ]);
    }

    /** @test */
    public function al_cerrar_expediente_sin_honorarios_no_genera_comision(): void
    {
        $exp = $this->makeExpediente(['honorarios_monto' => 0]);

        $exp->update(['estado' => 'cerrado']);

        $this->assertDatabaseMissing('comisiones', [
            'expediente_id' => $exp->id,
        ]);
    }

    /** @test */
    public function cerrar_expediente_dos_veces_no_duplica_comision(): void
    {
        $exp = $this->makeExpediente(['honorarios_monto' => 50000]);

        $exp->update(['estado' => 'cerrado']);
        // Simular re-guardado en estado cerrado
        $exp->update(['notas_internas' => 'cambio menor']);

        $count = Comision::where('expediente_id', $exp->id)->count();
        $this->assertEquals(1, $count);
    }

    // ── Ownership scope ───────────────────────────────────────────────────────

    /** @test */
    public function asesor_solo_ve_sus_expedientes(): void
    {
        $otroAsesor = User::factory()->create(['activo' => true]);
        $otroAsesor->assignRole('asesor');

        $propio = $this->makeExpediente();
        $ajeno  = Expediente::create([
            'tipo_tramite_id'   => $this->tipoTramite->id,
            'etapa_tramite_id'  => $this->etapa->id,
            'asesor_id'         => $otroAsesor->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Otro',
        ]);

        $visibles = Expediente::where('asesor_id', $this->asesor->id)->pluck('id');

        $this->assertContains($propio->id, $visibles);
        $this->assertNotContains($ajeno->id, $visibles);
    }
}
