<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\Configuracion;
use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\AvisoTramiteAcreditado;
use App\Notifications\CuvRegistrada;
use App\Notifications\SeguimientoActualizado;
use App\Services\EncargoGestorService;
use App\Services\ValidacionDocumentoService;
use App\Support\SeguimientoExpediente as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Seguimiento fijo del trámite (7 pasos automáticos), CUV, reglas de
 * documentos validados y bandeja de notificaciones.
 */
class SeguimientoCuvTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private User $gestor;
    private Acreditado $acreditado;
    private Expediente $exp;
    private DocumentoExpediente $docA;
    private DocumentoExpediente $docB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local'); Notification::fake(); Mail::fake(); Http::fake();

        foreach (['asesor', 'super_admin', 'gestor'] as $r) Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        $this->admin  = User::factory()->create(['activo' => true]); $this->admin->assignRole('super_admin');
        $this->asesor = User::factory()->create(['activo' => true, 'name' => 'Juan Pérez']); $this->asesor->assignRole('asesor');
        $this->gestor = User::factory()->create(['activo' => true, 'name' => 'Gestor Gómez']); $this->gestor->assignRole('gestor');

        $tipo  = TipoTramite::create(['nombre' => 'FOVISSSTE', 'slug' => 'fov-seg', 'porcentaje_honorarios' => 8]);
        $etapa = EtapaTramite::create(['tipo_tramite_id' => $tipo->id, 'nombre' => 'Inicio', 'orden' => 1]);

        $this->acreditado = Acreditado::create(['name' => 'María', 'email' => 'maria@example.com', 'password' => 'secreto123', 'telefono' => '7719998888']);
        $this->exp = Expediente::create([
            'asesor_id' => $this->asesor->id, 'acreditado_id' => $this->acreditado->id, 'acreditado_nombre' => 'María',
            'tipo_tramite_id' => $tipo->id, 'etapa_tramite_id' => $etapa->id, 'estado' => 'en_proceso',
        ]);
        $this->docA = $this->doc('INE');
        $this->docB = $this->doc('Comprobante de domicilio');
        Notification::fake(); // limpiar avisos de creación
    }

    private function doc(string $nombre, array $extra = []): DocumentoExpediente
    {
        Storage::disk('local')->put("expedientes/{$this->exp->id}/docs/" . md5($nombre) . '.pdf', 'x');
        return DocumentoExpediente::create(array_merge([
            'expediente_id' => $this->exp->id, 'tipo' => $nombre, 'nombre' => $nombre, 'seccion' => 'acreditado',
            'estado' => 'recibido', 'ruta_archivo' => "expedientes/{$this->exp->id}/docs/" . md5($nombre) . '.pdf',
        ], $extra));
    }

    private function validar(DocumentoExpediente $d): void
    {
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/documentos/{$d->id}/validar")->assertOk();
    }

    // ── Pasos automáticos ────────────────────────────────────────────────────

    public function test_nace_en_validacion_y_pasa_a_tramites_en_gestion_al_validar_todo(): void
    {
        $this->assertSame(S::VALIDACION_DOCUMENTOS, $this->exp->fresh()->seguimiento);

        $this->validar($this->docA);
        $this->assertSame(S::VALIDACION_DOCUMENTOS, $this->exp->fresh()->seguimiento, 'falta uno');
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => str_contains($n->titulo, 'Documento aprobado') && str_contains($n->cuerpo, 'INE'));

        $this->validar($this->docB);
        $this->assertSame(S::TRAMITES_EN_GESTION, $this->exp->fresh()->seguimiento);
        $this->assertNotNull($this->exp->fresh()->seguimiento_at);
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => $n->titulo === '📋 Trámites en gestión' && str_contains($n->cuerpo, 'validados'));
        Notification::assertSentTo($this->asesor, SeguimientoActualizado::class, fn ($n) => $n->nuevo === 'Trámites en gestión');
    }

    public function test_el_mensaje_del_paso_se_edita_en_el_panel(): void
    {
        Configuracion::set('seguimiento_msg_' . S::TRAMITES_EN_GESTION, 'Texto editado por la oficina.');
        $this->validar($this->docA); $this->validar($this->docB);

        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => $n->cuerpo === 'Texto editado por la oficina.');
        $this->actingAs($this->acreditado, 'sanctum')->getJson('/api/v1/acreditado/expediente')
            ->assertJsonPath('data.guia_paso_actual', 'Texto editado por la oficina.')
            ->assertJsonPath('data.seguimiento.paso', S::TRAMITES_EN_GESTION)
            ->assertJsonPath('data.seguimiento.orden', 2)
            ->assertJsonPath('data.seguimiento.total', 7);
    }

    public function test_rechazar_un_documento_despues_de_validar_todo_regresa_a_validacion(): void
    {
        $this->validar($this->docA); $this->validar($this->docB);
        Notification::fake();

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/documentos/{$this->docB->id}/rechazar", ['motivo' => 'Borroso'])->assertOk();

        $this->assertSame(S::VALIDACION_DOCUMENTOS, $this->exp->fresh()->seguimiento);
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => str_contains($n->cuerpo, 'rechazó'));
    }

    public function test_encargo_al_gestor_avisa_al_acreditado_y_al_terminar_pasa_a_gestionados(): void
    {
        $this->validar($this->docA); $this->validar($this->docB);
        $service = app(EncargoGestorService::class);
        $encargo = $service->enviar($this->exp, $this->gestor, [$this->docA->id], 'Tramitar en catastro', null, $this->admin);
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => str_contains($n->titulo, 'pasó al gestor'));

        // Gestor tramita: sube lo obtenido, «Ya terminé» y la administración lo valida
        $encargo->update(['estado' => EncargoGestor::EN_TRAMITE]);
        $docGestor = $service->subirDocumento($encargo, $this->gestor, UploadedFile::fake()->create('oficio.pdf', 10, 'application/pdf'), 'Oficio catastro', null);
        $service->entregar($encargo, $this->gestor);
        Notification::fake();
        app(ValidacionDocumentoService::class)->validar($docGestor->fresh(), $this->admin);

        $this->assertSame(EncargoGestor::TERMINADO, $encargo->fresh()->estado);
        $this->assertSame(S::TRAMITES_GESTIONADOS, $this->exp->fresh()->seguimiento);
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => str_contains($n->titulo, 'gestor terminó'));
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => $n->titulo === '📋 Trámites gestionados');
    }

    public function test_los_pasos_manuales_los_marca_solo_el_super_admin_y_no_retroceden(): void
    {
        $this->actingAs($this->asesor, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/seguimiento", ['paso' => S::EN_UNIDAD_VALUACION])->assertForbidden();
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/seguimiento", ['paso' => S::TRAMITES_EN_GESTION])->assertStatus(422);

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/seguimiento", ['paso' => S::FIRMA])
            ->assertOk()->assertJsonPath('seguimiento.paso', S::FIRMA)->assertJsonPath('seguimiento.orden', 6);
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => $n->titulo === '📋 Firma');

        // Ya en Firma: marcar valuación (paso 4) no retrocede
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/seguimiento", ['paso' => S::EN_UNIDAD_VALUACION])
            ->assertOk()->assertJsonPath('seguimiento.paso', S::FIRMA);
    }

    public function test_cerrar_el_expediente_lo_deja_en_credito_entregado(): void
    {
        $this->exp->update(['estado' => 'cerrado']);
        $this->assertSame(S::CREDITO_ENTREGADO, $this->exp->fresh()->seguimiento);
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => $n->titulo === '📋 Crédito entregado');
    }

    // ── CUV ──────────────────────────────────────────────────────────────────

    public function test_el_super_admin_registra_la_cuv_con_foto_y_avisa_a_todos(): void
    {
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/cuv", ['cuv' => '123456789012345'])->assertStatus(422);

        $this->actingAs($this->admin, 'sanctum')->post("/api/v1/expedientes/{$this->exp->id}/cuv", [
            'cuv' => '1234567890123456', 'archivo' => UploadedFile::fake()->image('cuv.jpg'),
        ])->assertOk()->assertJsonPath('cuv_info.valor', '1234567890123456')->assertJsonPath('seguimiento.paso', S::RECURSO_EN_GESTION);

        $exp = $this->exp->fresh();
        $this->assertSame('1234567890123456', $exp->cuv);
        $this->assertSame($this->admin->id, $exp->cuv_registrado_por);
        $this->assertDatabaseHas('documentos_expediente', ['expediente_id' => $exp->id, 'categoria' => 'cuv', 'tipo' => 'CUV']);
        Notification::assertSentTo($this->acreditado, AvisoTramiteAcreditado::class, fn ($n) => $n->titulo === '📋 Recurso en gestión' && str_contains($n->cuerpo, 'cuenta'));
        Notification::assertSentTo($this->asesor, CuvRegistrada::class);
        Notification::assertNotSentTo($this->admin, CuvRegistrada::class, 'quien la registra no se avisa a sí mismo');

        // El asesor la ve en su ficha, pero no puede registrarla
        $this->actingAs($this->asesor, 'sanctum')->getJson("/api/v1/expedientes/{$this->exp->id}")->assertJsonPath('data.cuv_info.valor', '1234567890123456')->assertJsonPath('data.seguimiento.paso', S::RECURSO_EN_GESTION);
        $this->actingAs($this->asesor, 'sanctum')->postJson("/api/v1/expedientes/{$this->exp->id}/cuv", ['cuv' => '1234567890123456'])->assertForbidden();
    }

    public function test_el_gestor_con_encargo_en_tramite_registra_la_cuv(): void
    {
        $encargo = app(EncargoGestorService::class)->enviar($this->exp, $this->gestor, [$this->docA->id], 'Valuación', null, $this->admin);
        $this->actingAs($this->gestor, 'sanctum')->postJson("/api/v1/gestor/encargos/{$encargo->id}/cuv", ['cuv' => '1234567890123456'])->assertStatus(422);   // aún por revisar

        $encargo->update(['estado' => EncargoGestor::EN_TRAMITE]);
        $this->actingAs($this->gestor, 'sanctum')->postJson("/api/v1/gestor/encargos/{$encargo->id}/cuv", ['cuv' => '1234567890123456'])->assertOk();

        $this->assertSame('1234567890123456', $this->exp->fresh()->cuv);
        Notification::assertSentTo($this->admin, CuvRegistrada::class);
    }

    // ── Reglas de documentos ─────────────────────────────────────────────────

    public function test_el_asesor_no_borra_ni_reemplaza_validados_ni_de_gestor_pero_el_admin_si(): void
    {
        $this->validar($this->docA);
        $archivo = UploadedFile::fake()->create('nuevo.pdf', 10, 'application/pdf');

        $this->actingAs($this->asesor, 'sanctum')->deleteJson("/api/v1/expedientes/{$this->exp->id}/documentos/{$this->docA->id}")->assertStatus(422);
        $this->actingAs($this->asesor, 'sanctum')->post("/api/v1/expedientes/{$this->exp->id}/documentos/{$this->docA->id}/reemplazar", ['archivo' => $archivo])->assertStatus(422);

        $docGestor = $this->doc('Oficio', ['encargo_gestor_id' => EncargoGestor::create(['expediente_id' => $this->exp->id, 'gestor_id' => $this->gestor->id, 'enviado_por_id' => $this->admin->id, 'estado' => 'en_tramite', 'instrucciones' => 'x'])->id]);
        $this->actingAs($this->asesor, 'sanctum')->deleteJson("/api/v1/expedientes/{$this->exp->id}/documentos/{$docGestor->id}")->assertStatus(422);

        $lista = $this->actingAs($this->asesor, 'sanctum')->getJson("/api/v1/expedientes/{$this->exp->id}/documentos")->assertOk()->json('data');
        $porId = collect($lista)->keyBy('id');
        $this->assertFalse($porId[$this->docA->id]['puede_modificar']);
        $this->assertTrue($porId[$this->docA->id]['validado']);
        $this->assertTrue($porId[$this->docB->id]['puede_modificar']);
        $this->assertTrue($porId[$docGestor->id]['de_gestor']);
        $this->assertFalse($porId[$docGestor->id]['puede_modificar']);

        // Pendiente/rechazado sí se puede; el admin puede todo
        $this->actingAs($this->asesor, 'sanctum')->post("/api/v1/expedientes/{$this->exp->id}/documentos/{$this->docB->id}/reemplazar", ['archivo' => UploadedFile::fake()->create('b.pdf', 10, 'application/pdf')])->assertOk();
        $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/expedientes/{$this->exp->id}/documentos/{$this->docA->id}")->assertOk();
    }

    // ── Bandeja de notificaciones ────────────────────────────────────────────

    public function test_la_bandeja_lista_marca_leidas_y_nunca_borra(): void
    {
        // Canal database real: quitar el fake que puso setUp()
        Notification::swap(new \Illuminate\Notifications\ChannelManager($this->app));
        $this->validar($this->docA); $this->validar($this->docB);

        // Acreditado: historial con texto y ruta
        $res = $this->actingAs($this->acreditado, 'sanctum')->getJson('/api/v1/acreditado/notificaciones')->assertOk();
        $this->assertGreaterThanOrEqual(3, $res->json('meta.total'));
        $this->assertSame($res->json('meta.total'), $res->json('meta.no_leidas'));
        $primera = $res->json('data.0');
        $this->assertFalse($primera['leida']);
        $this->assertNotEmpty($primera['titulo']);
        $this->assertContains($primera['ruta']['screen'], ['tramite', 'documentos']);
        $this->assertStringNotContainsString('**', $primera['cuerpo']);

        $this->actingAs($this->acreditado, 'sanctum')->putJson("/api/v1/acreditado/notificaciones/{$primera['id']}/leer")->assertOk()->assertJsonPath('data.leida', true);
        $this->actingAs($this->acreditado, 'sanctum')->getJson('/api/v1/acreditado/notificaciones/no-leidas')->assertJsonPath('no_leidas', $res->json('meta.total') - 1);
        $this->actingAs($this->acreditado, 'sanctum')->putJson('/api/v1/acreditado/notificaciones/leer-todas')->assertJsonPath('no_leidas', 0);
        $this->actingAs($this->acreditado, 'sanctum')->deleteJson("/api/v1/acreditado/notificaciones/{$primera['id']}")->assertStatus(404);   // no existe ruta para borrar

        // Asesor: la del seguimiento trae ruta a su expediente
        $asesorRes = $this->actingAs($this->asesor, 'sanctum')->getJson('/api/v1/notificaciones')->assertOk();
        $seg = collect($asesorRes->json('data'))->firstWhere('tipo', 'SeguimientoActualizado');
        $this->assertSame('expedientes', $seg['ruta']['screen']);
        $this->assertSame((string) $this->exp->id, $seg['ruta']['id']);

        // Gestor también tiene bandeja (no lo bloquea el middleware)
        $this->actingAs($this->gestor, 'sanctum')->getJson('/api/v1/notificaciones')->assertOk();
    }
}
