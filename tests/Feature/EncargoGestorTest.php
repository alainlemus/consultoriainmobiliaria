<?php

namespace Tests\Feature;

use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\EncargoGestorNotificacion;
use App\Services\EncargoGestorService;
use App\Services\ValidacionDocumentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ida y vuelta de un encargo: el super_admin envía, el gestor revisa (✓/✗ con nota),
 * el admin corrige y reenvía, el gestor empieza el trámite, sube documentos y el
 * encargo se cierra cuando el admin valida todo. Avisos al gestor y a los super_admin.
 */
class EncargoGestorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gestor;
    private Expediente $exp;
    private DocumentoExpediente $escritura;
    private DocumentoExpediente $ine;
    private DocumentoExpediente $acta; // no se comparte

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);

        $this->admin  = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        $this->gestor = User::factory()->create(['activo' => true, 'name' => 'Juan Gestor']);
        $this->gestor->assignRole('gestor');

        $asesor = User::factory()->create(['activo' => true]);
        $asesor->assignRole('asesor');
        $tipo  = TipoTramite::create(['nombre' => 'FOVISSSTE Tradicional', 'slug' => 'fov-trad-gestor-test', 'porcentaje_honorarios' => 8.0]);
        $etapa = EtapaTramite::create(['tipo_tramite_id' => $tipo->id, 'nombre' => 'Documentación', 'orden' => 1]);
        $this->exp = Expediente::create([
            'asesor_id' => $asesor->id, 'tipo_tramite_id' => $tipo->id, 'etapa_tramite_id' => $etapa->id,
            'estado' => 'en_proceso', 'acreditado_nombre' => 'María Hernández', 'acreditado_telefono' => '7711234567',
        ]);

        $doc = fn ($n) => DocumentoExpediente::create([
            'expediente_id' => $this->exp->id, 'tipo' => $n, 'nombre' => $n, 'seccion' => 'otros',
            'estado' => 'recibido', 'ruta_archivo' => "expedientes/{$this->exp->id}/docs/" . str($n)->slug() . '.pdf',
        ]);
        $this->escritura = $doc('Escritura');
        $this->ine       = $doc('INE del vendedor');
        $this->acta      = $doc('Acta de nacimiento');
    }

    private function enviar(): EncargoGestor
    {
        return app(EncargoGestorService::class)->enviar(
            $this->exp, $this->gestor, [$this->escritura->id, $this->ine->id], 'Tramitar CLG en el Registro Público', '2026-11-08', $this->admin,
        );
    }

    public function test_el_admin_envia_y_el_gestor_recibe_aviso_y_solo_ve_lo_compartido(): void
    {
        $encargo = $this->enviar();

        $this->assertSame(EncargoGestor::POR_REVISAR, $encargo->estado);
        Notification::assertSentTo($this->gestor, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'enviado');

        Sanctum::actingAs($this->gestor);
        $this->getJson('/api/v1/gestor/encargos')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.expediente.acreditado', 'María Hernández');

        $show = $this->getJson("/api/v1/gestor/encargos/{$encargo->id}")->assertOk();
        $this->assertEqualsCanonicalizing(['Escritura', 'INE del vendedor'], collect($show->json('data.documentos'))->pluck('nombre')->all());
        // Datos del expediente: por defecto las 4 secciones; el teléfono del acreditado viene en la suya
        $datos = collect($show->json('data.datos'))->keyBy('clave');
        $this->assertSame(['acreditado', 'vendedor', 'inmueble', 'credito'], $datos->keys()->all());
        $this->assertContains(['label' => 'Teléfono', 'valor' => '7711234567'], $datos['acreditado']['campos']);

        // Descarga: lo compartido sí, lo demás no
        $this->getJson("/api/v1/gestor/encargos/{$encargo->id}/documentos/{$this->ine->id}/url")->assertOk()->assertJsonStructure(['url']);
        $this->getJson("/api/v1/gestor/encargos/{$encargo->id}/documentos/{$this->acta->id}/url")->assertNotFound();
    }

    public function test_otro_gestor_no_ve_el_encargo(): void
    {
        $encargo = $this->enviar();
        $otro = User::factory()->create(['activo' => true]);
        $otro->assignRole('gestor');

        Sanctum::actingAs($otro);
        $this->getJson('/api/v1/gestor/encargos')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/gestor/encargos/{$encargo->id}")->assertNotFound();
    }

    public function test_un_asesor_no_entra_a_la_api_del_gestor(): void
    {
        $asesor = User::factory()->create(['activo' => true]);
        $asesor->assignRole('asesor');
        Sanctum::actingAs($asesor);

        $this->getJson('/api/v1/gestor/encargos')->assertForbidden();
    }

    public function test_ida_y_vuelta_completo_hasta_terminar(): void
    {
        $encargo = $this->enviar();
        Sanctum::actingAs($this->gestor);

        // No puede subir antes de revisar
        $this->post("/api/v1/gestor/encargos/{$encargo->id}/documentos", ['archivo' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'), 'tipo' => 'CLG'], ['Accept' => 'application/json'])
            ->assertStatus(422);

        // Marcar incorrecto sin nota → error
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/revision", ['revisiones' => [
            ['documento_id' => $this->escritura->id, 'revision' => 'correcto'],
            ['documento_id' => $this->ine->id, 'revision' => 'incorrecto'],
        ]])->assertStatus(422);

        // Revisión con observaciones → avisa a los super_admin
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/revision", ['revisiones' => [
            ['documento_id' => $this->escritura->id, 'revision' => 'correcto'],
            ['documento_id' => $this->ine->id, 'revision' => 'incorrecto', 'nota' => 'Está vencida'],
        ]])->assertOk()->assertJsonPath('data.estado', EncargoGestor::CON_OBSERVACIONES);
        Notification::assertSentTo($this->admin, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'con_observaciones' && str_contains($n->mensaje, 'Está vencida'));

        // Reenviar sin reemplazar → error claro
        $service = app(EncargoGestorService::class);
        try {
            $service->reenviar($encargo->fresh(), $this->admin);
            $this->fail('Debió pedir reemplazar el documento');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('INE del vendedor', collect($e->errors())->flatten()->join(' '));
        }

        // El admin reemplaza la INE y reenvía → el gestor solo revisa la INE
        $this->travel(1)->minutes();
        $this->ine->update(['ruta_archivo' => "expedientes/{$this->exp->id}/docs/ine-nueva.pdf"]);
        $service->reenviar($encargo->fresh(), $this->admin);
        Notification::assertSentTo($this->gestor, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'reenviado');

        $show = $this->getJson("/api/v1/gestor/encargos/{$encargo->id}")->assertJsonPath('data.estado', EncargoGestor::POR_REVISAR)->assertJsonPath('data.vuelta', 2);
        $docs = collect($show->json('data.documentos'))->keyBy('nombre');
        $this->assertSame('correcto', $docs['Escritura']['revision']);
        $this->assertSame('pendiente', $docs['INE del vendedor']['revision']);
        $this->assertTrue($docs['INE del vendedor']['nuevo']);

        // Todo correcto → en trámite
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/revision", ['revisiones' => [
            ['documento_id' => $this->ine->id, 'revision' => 'correcto'],
        ]])->assertOk()->assertJsonPath('data.estado', EncargoGestor::EN_TRAMITE);
        Notification::assertSentTo($this->admin, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'tramite_iniciado');

        // Sube un Word y un PDF
        $word = $this->post("/api/v1/gestor/encargos/{$encargo->id}/documentos", [
            'archivo' => UploadedFile::fake()->create('acuse.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'tipo' => 'Acuse', 'notas' => 'Con sello',
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $pdf = $this->post("/api/v1/gestor/encargos/{$encargo->id}/documentos", [
            'archivo' => UploadedFile::fake()->create('clg.pdf', 20, 'application/pdf'), 'tipo' => 'CLG',
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        Notification::assertSentTo($this->admin, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'documento_subido');

        // No acepta otros formatos
        $this->post("/api/v1/gestor/encargos/{$encargo->id}/documentos", [
            'archivo' => UploadedFile::fake()->create('x.zip', 5, 'application/zip'), 'tipo' => 'X',
        ], ['Accept' => 'application/json'])->assertStatus(422);

        // El admin rechaza el Word → aviso al gestor; lo vuelve a subir reemplazándolo
        $this->actingAs($this->admin);
        $validacion = app(ValidacionDocumentoService::class);
        $validacion->rechazar(DocumentoExpediente::find($word), 'Falta el sello');
        Notification::assertSentTo($this->gestor, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'documento_rechazado');

        Sanctum::actingAs($this->gestor);
        $this->post("/api/v1/gestor/encargos/{$encargo->id}/documentos", [
            'archivo' => UploadedFile::fake()->create('acuse2.pdf', 20, 'application/pdf'), 'tipo' => 'Acuse', 'reemplaza_documento_id' => $word,
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('data.id', $word);

        // Sin «Ya terminé» el encargo sigue abierto aunque todo esté validado
        $validacion->validar(DocumentoExpediente::find($pdf), $this->admin);
        $validacion->validar(DocumentoExpediente::find($word), $this->admin);
        $this->assertSame(EncargoGestor::EN_TRAMITE, $encargo->fresh()->estado);

        // Avisa que terminó y ya estaba todo validado: se cierra
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertOk()->assertJsonPath('data.estado', EncargoGestor::TERMINADO);
        Notification::assertSentTo($this->gestor, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'terminado');

        $this->assertGreaterThanOrEqual(8, $encargo->eventos()->count());
    }

    public function test_solo_se_puede_enviar_a_un_gestor_activo_y_con_documentos(): void
    {
        $service = app(EncargoGestorService::class);
        $this->expectException(ValidationException::class);
        $service->enviar($this->exp, $this->gestor, [], 'X', null, $this->admin);
    }

    public function test_el_admin_elige_que_datos_del_expediente_ve_el_gestor(): void
    {
        $encargo = app(EncargoGestorService::class)->enviar(
            $this->exp, $this->gestor, [$this->escritura->id], 'Avalúo', null, $this->admin, ['inmueble'],
        );

        Sanctum::actingAs($this->gestor);
        $datos = $this->getJson("/api/v1/gestor/encargos/{$encargo->id}")->assertOk()->json('data.datos');
        $this->assertSame(['inmueble'], array_column($datos, 'clave'));
    }

    public function test_el_gestor_ve_sus_encargos_en_la_web(): void
    {
        $this->enviar();

        $this->actingAs($this->gestor)->get('/admin/gestor')->assertOk()
            ->assertSee('María Hernández')
            ->assertSee('Tramitar CLG en el Registro Público')
            ->assertSee('INE del vendedor')
            ->assertSee('Datos del acreditado')
            ->assertDontSee('Acta de nacimiento');
    }

    public function test_el_super_admin_envia_y_reenvia_desde_el_panel(): void
    {
        $this->actingAs($this->admin);
        $rm = \Livewire\Livewire::test(\App\Filament\Resources\ExpedienteResource\RelationManagers\EncargosGestorRelationManager::class, [
            'ownerRecord' => $this->exp,
            'pageClass'   => \App\Filament\Resources\ExpedienteResource\Pages\EditExpediente::class,
        ])->assertOk();

        $rm->callTableAction('enviar_a_gestor', data: [
            'gestor_id'     => $this->gestor->id,
            'instrucciones' => 'Tramitar CLG',
            'documentos'    => [$this->escritura->id, $this->ine->id],
            'datos'         => ['acreditado', 'inmueble'],
        ])->assertHasNoTableActionErrors();

        $encargo = EncargoGestor::firstOrFail();
        $this->assertSame(2, $encargo->documentos()->count());
        // (el helper de test mezcla los datos con el default de la lista; en el navegador llega solo lo marcado)
        $this->assertContains('acreditado', $encargo->datos_compartidos);
        $this->assertContains('inmueble', $encargo->datos_compartidos);

        // El gestor marca la INE mal; el admin sube la buena desde "Corregir y reenviar"
        app(EncargoGestorService::class)->enviarRevision($encargo, $this->gestor, [
            ['documento_id' => $this->escritura->id, 'revision' => 'correcto'],
            ['documento_id' => $this->ine->id, 'revision' => 'incorrecto', 'nota' => 'Vencida'],
        ]);
        $this->travel(1)->minutes();

        $rm->callTableAction('reenviar', $encargo->fresh(), data: [
            "archivo_{$this->ine->id}" => UploadedFile::fake()->create('ine.pdf', 20, 'application/pdf'),
        ])->assertHasNoTableActionErrors();

        $this->assertSame(EncargoGestor::POR_REVISAR, $encargo->fresh()->estado);
        $this->assertSame(2, $encargo->fresh()->vuelta);
    }

    public function test_un_asesor_no_ve_la_pestana_de_gestores(): void
    {
        $asesor = User::factory()->create(['activo' => true]);
        $asesor->assignRole('asesor');
        $this->actingAs($asesor);

        $this->assertFalse(\App\Filament\Resources\ExpedienteResource\RelationManagers\EncargosGestorRelationManager::canViewForRecord(
            $this->exp, \App\Filament\Resources\ExpedienteResource\Pages\EditExpediente::class,
        ));
    }

    // ── «Ya terminé», reabrir y recordatorio ─────────────────────────────────

    private function enTramite(): EncargoGestor
    {
        $encargo = $this->enviar();
        Sanctum::actingAs($this->gestor);
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/revision", ['revisiones' => [
            ['documento_id' => $this->escritura->id, 'revision' => 'correcto'],
            ['documento_id' => $this->ine->id, 'revision' => 'correcto'],
        ]])->assertOk();

        return $encargo->fresh();
    }

    private function subir(EncargoGestor $encargo, string $tipo = 'CLG'): DocumentoExpediente
    {
        $id = $this->post("/api/v1/gestor/encargos/{$encargo->id}/documentos", [
            'archivo' => UploadedFile::fake()->create('a.pdf', 20, 'application/pdf'), 'tipo' => $tipo,
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');

        return DocumentoExpediente::find($id);
    }

    public function test_validar_lo_subido_no_cierra_si_el_gestor_no_ha_terminado(): void
    {
        $encargo = $this->enTramite();
        $doc = $this->subir($encargo);

        app(ValidacionDocumentoService::class)->validar($doc, $this->admin);
        $this->assertSame(EncargoGestor::EN_TRAMITE, $encargo->fresh()->estado);

        // Puede seguir subiendo lo que le falta
        $this->subir($encargo, 'Acuse');
        $this->assertCount(2, $encargo->documentosSubidos()->get());
    }

    public function test_ya_termine_sin_validar_espera_al_admin_y_se_cierra_al_validar_todo(): void
    {
        $encargo = $this->enTramite();
        $a = $this->subir($encargo, 'CLG');
        $b = $this->subir($encargo, 'Acuse');

        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertOk()
            ->assertJsonPath('data.estado', EncargoGestor::POR_VALIDAR)
            ->assertJsonPath('data.estado_label', 'Por validar');
        Notification::assertSentTo($this->admin, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'entregado' && str_contains($n->mensaje, 'Faltan 2'));

        // Sigue en la lista de activos y ya no se puede subir
        $this->getJson('/api/v1/gestor/encargos')->assertOk()->assertJsonCount(1, 'data');
        $this->post("/api/v1/gestor/encargos/{$encargo->id}/documentos", [
            'archivo' => UploadedFile::fake()->create('x.pdf', 20, 'application/pdf'), 'tipo' => 'X',
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $validacion = app(ValidacionDocumentoService::class);
        $validacion->validar($a, $this->admin);
        $this->assertSame(EncargoGestor::POR_VALIDAR, $encargo->fresh()->estado);
        $validacion->validar($b, $this->admin);
        $this->assertSame(EncargoGestor::TERMINADO, $encargo->fresh()->estado);
        Notification::assertSentTo($this->gestor, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'terminado');
    }

    public function test_no_puede_terminar_sin_subir_nada_ni_con_un_rechazado(): void
    {
        $encargo = $this->enTramite();
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertStatus(422);

        $doc = $this->subir($encargo);
        $this->actingAs($this->admin);
        app(ValidacionDocumentoService::class)->rechazar($doc, 'Ilegible');

        Sanctum::actingAs($this->gestor);
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertStatus(422);
        $this->assertSame(EncargoGestor::EN_TRAMITE, $encargo->fresh()->estado);
    }

    public function test_un_rechazo_despues_de_terminar_le_regresa_el_encargo_al_gestor(): void
    {
        $encargo = $this->enTramite();
        $doc = $this->subir($encargo);
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertOk();

        $this->actingAs($this->admin);
        app(ValidacionDocumentoService::class)->rechazar($doc, 'Falta el sello');

        $fresco = $encargo->fresh();
        $this->assertSame(EncargoGestor::EN_TRAMITE, $fresco->estado);
        $this->assertNull($fresco->entregado_at);
        Notification::assertSentTo($this->gestor, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'documento_rechazado');
    }

    public function test_el_gestor_puede_reabrir_mientras_esta_por_validar(): void
    {
        $encargo = $this->enTramite();
        $this->subir($encargo);

        // No se reabre lo que no está por validar
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/reabrir")->assertStatus(422);

        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertOk();
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/reabrir")->assertOk()
            ->assertJsonPath('data.estado', EncargoGestor::EN_TRAMITE);
        Notification::assertSentTo($this->admin, EncargoGestorNotificacion::class, fn ($n) => $n->tipo === 'reabierto');

        $this->subir($encargo, 'Acuse');
        $this->assertCount(2, $encargo->documentosSubidos()->get());
    }

    public function test_otro_gestor_no_puede_terminar_ni_reabrir(): void
    {
        $encargo = $this->enTramite();
        $this->subir($encargo);
        $otro = User::factory()->create(['activo' => true]);
        $otro->assignRole('gestor');

        Sanctum::actingAs($otro);
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertNotFound();
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/reabrir")->assertNotFound();
    }

    public function test_recuerda_al_admin_a_los_2_dias_y_luego_cada_2_dias(): void
    {
        $recordatorios = fn () => Notification::sent($this->admin, EncargoGestorNotificacion::class)
            ->filter(fn ($n) => $n->tipo === 'recordatorio_validacion')->count();

        $encargo = $this->enTramite();
        $this->subir($encargo);
        $this->travelTo('2026-11-02 18:00:00');
        $this->postJson("/api/v1/gestor/encargos/{$encargo->id}/entregar")->assertOk();

        $correr = fn (string $cuando) => tap($this->travelTo($cuando), fn () => $this->artisan('encargos:recordar-validacion')->assertSuccessful());

        $correr('2026-11-03 15:00:00'); // 1 día: todavía no
        $this->assertSame(0, $recordatorios());

        $correr('2026-11-04 15:00:00'); // 2 días: primero
        $this->assertSame(1, $recordatorios());

        $correr('2026-11-05 15:00:00'); // al día siguiente: no se repite
        $this->assertSame(1, $recordatorios());

        $correr('2026-11-06 15:00:00'); // 2 días después: otro
        $this->assertSame(2, $recordatorios());

        // Ya validado y cerrado: no se recuerda más
        app(ValidacionDocumentoService::class)->validar($encargo->documentosSubidos()->first(), $this->admin);
        $this->assertSame(EncargoGestor::TERMINADO, $encargo->fresh()->estado);
        $correr('2026-11-10 15:00:00');
        $this->assertSame(2, $recordatorios());
    }
}
