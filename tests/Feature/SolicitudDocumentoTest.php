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

    public function test_la_carpeta_solo_se_envia_con_todo_validado_y_sin_documentos_solicitados_pendientes(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->actingAs($this->admin, 'sanctum');

        // Todo validado: se puede enviar
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/enviar-carpeta")->assertOk();

        // Si a destiempo falta un documento, deja de poder enviarse hasta que se suba y se valide
        $doc = app(\App\Services\SolicitudDocumentoService::class)->solicitar($this->exp, $this->admin, 'Falta el RFC', 'asesor');
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/enviar-carpeta")->assertStatus(422);

        $doc->update(['ruta_archivo' => 'rfc.pdf', 'estado' => 'recibido']);
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/enviar-carpeta")->assertStatus(422);   // falta validarlo

        $doc->update(['validado_at' => now()]);
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/enviar-carpeta")->assertOk();
    }

    public function test_el_admin_puede_pedirselo_al_asesor_y_el_acreditado_no_lo_ve(): void
    {
        Notification::fake();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/expedientes/{$this->exp->id}/documentos-solicitados", ['nota' => 'Falta el RFC del vendedor', 'para' => 'asesor'])
            ->assertCreated();

        Notification::assertSentTo($this->asesor, DocumentoSolicitado::class, fn ($n) => $n->documento->solicitado_a === 'asesor');
        Notification::assertNotSentTo($this->acreditado, DocumentoSolicitado::class);

        // El asesor lo ve en los documentos del expediente
        $this->actingAs($this->asesor, 'sanctum')
            ->getJson("/api/v1/expedientes/{$this->exp->id}/documentos")
            ->assertOk()
            ->assertJsonFragment(['solicitud_nota' => 'Falta el RFC del vendedor', 'solicitado_a' => 'asesor']);

        // El acreditado no: ni en Mi Trámite ni como pendiente
        $this->actingAs($this->acreditado, 'sanctum')
            ->getJson('/api/v1/acreditado/expediente')
            ->assertJsonPath('data.documentos_solicitados', [])
            ->assertJsonPath('data.documentos_pendientes', 0);
    }

    public function test_sin_acreditado_en_la_app_siempre_se_le_pide_al_asesor(): void
    {
        Notification::fake();
        $this->exp->update(['acreditado_id' => null]);

        $doc = app(\App\Services\SolicitudDocumentoService::class)->solicitar($this->exp->fresh(), $this->admin, 'Falta algo', 'acreditado');

        $this->assertSame('asesor', $doc->solicitado_a);
        Notification::assertSentTo($this->asesor, DocumentoSolicitado::class);
    }

    public function test_el_asesor_puede_subir_el_documento_solicitado_con_reemplazar(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $doc = app(\App\Services\SolicitudDocumentoService::class)->solicitar($this->exp, $this->admin, 'Falta el RFC', 'asesor');

        $this->actingAs($this->asesor, 'sanctum')
            ->post("/api/v1/expedientes/{$this->exp->id}/documentos/{$doc->id}/reemplazar", [
                'archivo' => \Illuminate\Http\UploadedFile::fake()->create('rfc.pdf', 20, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertFalse($this->exp->fresh()->documentos()->solicitadosPendientes()->exists());
        $this->assertNull($doc->fresh()->validado_at);   // el admin debe validarlo
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
