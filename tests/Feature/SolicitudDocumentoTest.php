<?php

namespace Tests\Feature;

use App\Filament\Resources\ExpedienteResource\Pages\EditExpediente;
use App\Filament\Resources\ExpedienteResource\RelationManagers\DocumentosRelationManager;
use App\Models\Acreditado;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\DocumentoSolicitado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SolicitudDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private Acreditado $acreditado;
    private Expediente $exp;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);
        $this->admin  = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        $this->acreditado = Acreditado::create(['name' => 'Cliente', 'email' => 'c@example.com', 'password' => Hash::make('x12345678'), 'activo' => true]);
        $tipo = TipoTramite::create(['nombre' => 'FOVISSSTE', 'slug' => 'fov-sol', 'porcentaje_honorarios' => 8]);
        $this->exp = Expediente::create(['asesor_id' => $this->asesor->id, 'tipo_tramite_id' => $tipo->id, 'acreditado_id' => $this->acreditado->id, 'estado' => 'en_proceso', 'acreditado_nombre' => 'Cliente']);
        // Todo validado: expediente completo
        DocumentoExpediente::create(['expediente_id' => $this->exp->id, 'tipo' => 'INE', 'nombre' => 'INE', 'seccion' => 'acreditado', 'estado' => 'recibido', 'ruta_archivo' => 'a.pdf', 'validado_at' => now()]);
    }

    public function test_el_admin_solicita_un_documento_y_el_acreditado_lo_ve_y_recibe_push(): void
    {
        Notification::fake();
        $this->assertTrue($this->exp->estaCompleto());

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/expedientes/{$this->exp->id}/documentos-solicitados", ['nota' => 'Falta el acta de matrimonio'])
            ->assertCreated();

        Notification::assertSentTo($this->acreditado, DocumentoSolicitado::class, fn ($n) => $n->documento->solicitud_nota === 'Falta el acta de matrimonio');
        Notification::assertSentTo($this->asesor, DocumentoSolicitado::class);

        // Ya no está completo y el acreditado lo ve en Mi Trámite, en revisión
        $this->assertFalse($this->exp->fresh()->estaCompleto());
        $this->actingAs($this->acreditado, 'sanctum')
            ->getJson('/api/v1/acreditado/expediente')
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_revision')
            ->assertJsonPath('data.documentos_solicitados.0.nota', 'Falta el acta de matrimonio')
            ->assertJsonPath('data.documentos_pendientes', 1);
    }

    public function test_al_subir_el_documento_solicitado_deja_de_estar_pendiente_y_espera_validacion(): void
    {
        $doc = app(\App\Services\SolicitudDocumentoService::class)->solicitar($this->exp, $this->admin, 'Falta el acta');
        $doc->update(['ruta_archivo' => 'acta.pdf', 'estado' => 'recibido']);

        $this->assertFalse($this->exp->fresh()->documentos()->solicitadosPendientes()->exists());
        $this->assertFalse($this->exp->fresh()->estaCompleto());   // falta que el admin lo valide

        $doc->update(['validado_at' => now()]);
        $this->assertTrue($this->exp->fresh()->estaCompleto());
    }

    public function test_solo_un_administrador_puede_solicitarlo(): void
    {
        $this->actingAs($this->asesor, 'sanctum')
            ->postJson("/api/v1/expedientes/{$this->exp->id}/documentos-solicitados", ['nota' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/expedientes/{$this->exp->id}/documentos-solicitados", [])
            ->assertUnprocessable();
    }

    public function test_el_admin_ve_en_la_app_un_expediente_completo_y_el_asesor_no(): void
    {
        $this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/expedientes')->assertJsonPath('meta.total', 0);
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/expedientes')->assertJsonPath('meta.total', 1);
    }

    public function test_el_panel_tiene_la_accion_solo_para_el_admin(): void
    {
        Notification::fake();
        foreach (['ViewAny:Expediente', 'View:Expediente', 'Update:Expediente'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $this->admin->syncPermissions(['ViewAny:Expediente', 'View:Expediente', 'Update:Expediente']);
        $this->actingAs($this->admin);

        Livewire::test(DocumentosRelationManager::class, ['ownerRecord' => $this->exp, 'pageClass' => EditExpediente::class])
            ->callTableAction('solicitar_documento', data: ['nota' => 'Falta el acta'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Falta el acta', $this->exp->documentos()->solicitadosPendientes()->first()->solicitud_nota);
    }
}
