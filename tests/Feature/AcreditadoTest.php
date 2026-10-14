<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\DeviceToken;
use App\Models\DocumentoExpediente;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\DocumentoRechazado;
use App\Notifications\EtapaExpedienteCambiada;
use App\Services\PushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cubre las features recientes que involucran al Acreditado:
 *  - Registro de dispositivo (push) polimórfico — User o Acreditado
 *  - PushService envía solo a los tokens del notifiable correcto
 *  - Documento rechazado: endpoint del asesor + notificación al acreditado
 *  - Cambio de etapa: el acreditado vinculado también se entera (solo push)
 */
class AcreditadoTest extends TestCase
{
    use RefreshDatabase;

    private User $asesor;
    private User $otroAsesor;
    private TipoTramite $tipo;
    private EtapaTramite $etapa1;
    private EtapaTramite $etapa2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->asesor     = User::factory()->create(['activo' => true]);
        $this->otroAsesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');
        $this->otroAsesor->assignRole('asesor');

        $this->tipo = TipoTramite::create([
            'nombre'                => 'FOVISSSTE Tradicional',
            'slug'                  => 'fovissste-tradicional-acreditado-test',
            'porcentaje_honorarios' => 8.0,
        ]);

        $this->etapa1 = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipo->id,
            'nombre'          => 'Precalificación',
            'orden'           => 1,
        ]);

        $this->etapa2 = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipo->id,
            'nombre'          => 'Documentación',
            'orden'           => 2,
        ]);
    }

    private function crearAcreditado(array $extra = []): Acreditado
    {
        return Acreditado::create(array_merge([
            'name'     => 'Cliente App',
            'email'    => 'cliente' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
            'activo'   => true,
        ], $extra));
    }

    private function crearExpediente(array $extra = []): Expediente
    {
        return Expediente::create(array_merge([
            'asesor_id'         => $this->asesor->id,
            'tipo_tramite_id'   => $this->tipo->id,
            'etapa_tramite_id'  => $this->etapa1->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Cliente Test',
        ], $extra));
    }

    // ─── Dispositivo (push) ─────────────────────────────────────────────────

    public function test_expediente_abierto_en_el_panel_para_su_prospecto_aparece_en_la_app_del_acreditado(): void
    {
        $contacto   = \App\Models\Contacto::create(['nombre' => 'Cliente App', 'telefono' => '5522222222']);
        $acreditado = $this->crearAcreditado(['contacto_id' => $contacto->id]);
        $exp        = $this->crearExpediente(['contacto_id' => $contacto->id]);

        $this->assertNull($exp->fresh()->acreditado_id);

        $this->actingAs($acreditado, 'sanctum')
            ->getJson('/api/v1/acreditado/expediente')
            ->assertOk()
            ->assertJsonPath('data.id', $exp->id);

        $this->assertSame($acreditado->id, $exp->fresh()->acreditado_id);
    }

    public function test_el_expediente_de_otro_prospecto_no_se_liga_al_acreditado(): void
    {
        $acreditado = $this->crearAcreditado();
        $this->crearExpediente(['contacto_id' => \App\Models\Contacto::create(['nombre' => 'Otro', 'telefono' => '5533333333'])->id]);

        $this->actingAs($acreditado, 'sanctum')
            ->getJson('/api/v1/acreditado/expediente')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_la_nota_del_documento_subido_por_el_acreditado_se_guarda_y_se_ve_en_el_panel(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('private');
        $acreditado = $this->crearAcreditado();
        $exp        = $this->crearExpediente(['acreditado_id' => $acreditado->id]);

        $this->actingAs($acreditado, 'sanctum')
            ->post('/api/v1/acreditado/expediente/documentos', [
                'archivo'        => \Illuminate\Http\UploadedFile::fake()->create('luz.pdf', 50, 'application/pdf'),
                'tipo_documento' => 'Comprobante de domicilio',
                'notas'          => 'Recibo de luz de mi casa',
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $doc = $exp->documentos()->where('tipo', 'Comprobante de domicilio')->first();
        $this->assertSame('Recibo de luz de mi casa', $doc->notas);

        // Panel: la columna "Nota" del expediente la muestra
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\ExpedienteResource\RelationManagers\DocumentosRelationManager::class, [
            'ownerRecord' => $exp,
            'pageClass'   => \App\Filament\Resources\ExpedienteResource\Pages\EditExpediente::class,
        ])
            ->assertCanSeeTableRecords([$doc])
            ->assertTableColumnStateSet('notas', 'Recibo de luz de mi casa', $doc);
    }

    public function test_el_perfil_indica_si_el_acreditado_ya_envio_su_solicitud(): void
    {
        $acreditado = $this->crearAcreditado();
        $this->assertFalse($acreditado->toApiArray()['solicitud_enviada']);

        \App\Models\AcreditadoSolicitud::create([
            'acreditado_id'    => $acreditado->id,
            'servicio'         => 'Crédito Tradicional FOVISSSTE',
            'estado_solicitud' => 'pendiente',
        ]);

        $this->assertTrue($acreditado->fresh()->toApiArray()['solicitud_enviada']);
    }

    public function test_acreditado_puede_registrar_su_dispositivo(): void
    {
        $acreditado = $this->crearAcreditado();

        $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/dispositivos', [
                'fcm_token'  => 'token-acreditado-abc',
                'plataforma' => 'ios',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('device_tokens', [
            'fcm_token'      => 'token-acreditado-abc',
            'tokenable_type' => Acreditado::class,
            'tokenable_id'   => $acreditado->id,
            'user_id'        => null,
        ]);
    }

    public function test_dispositivo_de_asesor_sigue_llenando_user_id(): void
    {
        // El endpoint es compartido (mismo controller) — no debe romper el
        // caso asesor al generalizar el polimorfismo.
        $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/dispositivos', [
                'fcm_token'  => 'token-asesor-xyz',
                'plataforma' => 'android',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('device_tokens', [
            'fcm_token'      => 'token-asesor-xyz',
            'tokenable_type' => User::class,
            'tokenable_id'   => $this->asesor->id,
            'user_id'        => $this->asesor->id,
        ]);
    }

    public function test_mismo_fcm_token_reasigna_tokenable_al_reregistrarse(): void
    {
        // Mismo dispositivo, primero un asesor lo usa, luego un acreditado
        // (dispositivo compartido / demo) — updateOrCreate por fcm_token debe
        // reasignar el dueño, no duplicar filas.
        $acreditado = $this->crearAcreditado();

        $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/dispositivos', ['fcm_token' => 'token-compartido', 'plataforma' => 'android']);

        $this->actingAs($acreditado, 'sanctum')
            ->postJson('/api/v1/acreditado/dispositivos', ['fcm_token' => 'token-compartido', 'plataforma' => 'android']);

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', [
            'fcm_token'      => 'token-compartido',
            'tokenable_type' => Acreditado::class,
            'tokenable_id'   => $acreditado->id,
            'user_id'        => null,
        ]);
    }

    // ─── Documento rechazado ────────────────────────────────────────────────

    public function test_admin_puede_rechazar_documento_con_motivo(): void
    {
        Notification::fake();

        $acreditado = $this->crearAcreditado();
        $exp = $this->crearExpediente(['acreditado_id' => $acreditado->id]);
        $doc = DocumentoExpediente::create([
            'expediente_id' => $exp->id,
            'tipo'          => 'ine',
            'nombre'        => 'INE',
            'seccion'       => 'acreditado',
            'estado'        => 'recibido',
            'ruta_archivo'  => 'expedientes/1/docs/ine.jpg',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", [
                'motivo' => 'La foto sale borrosa, no se alcanza a leer la CURP',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'rechazado');

        $this->assertDatabaseHas('documentos_expediente', [
            'id'     => $doc->id,
            'estado' => 'rechazado',
            'motivo_rechazo' => 'La foto sale borrosa, no se alcanza a leer la CURP',
        ]);

        Notification::assertSentTo(
            $acreditado,
            DocumentoRechazado::class,
            fn (DocumentoRechazado $n) => $n->motivo === 'La foto sale borrosa, no se alcanza a leer la CURP'
        );
    }

    public function test_rechazar_documento_sin_motivo_falla_validacion(): void
    {
        $exp = $this->crearExpediente();
        $doc = DocumentoExpediente::create([
            'expediente_id' => $exp->id,
            'tipo'          => 'ine',
            'nombre'        => 'INE',
            'seccion'       => 'acreditado',
            'estado'        => 'recibido',
            'ruta_archivo'  => 'expedientes/1/docs/ine.jpg',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", [])
            ->assertStatus(422);
    }

    public function test_rechazar_documento_sin_archivo_falla(): void
    {
        $exp = $this->crearExpediente();
        $doc = DocumentoExpediente::create([
            'expediente_id' => $exp->id,
            'tipo'          => 'ine',
            'nombre'        => 'INE',
            'seccion'       => 'acreditado',
            'estado'        => 'pendiente',
            'ruta_archivo'  => null,
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", [
                'motivo' => 'No hay nada que rechazar',
            ])
            ->assertStatus(422);
    }

    public function test_un_asesor_no_puede_rechazar_ni_validar_documentos(): void
    {
        $exp = $this->crearExpediente(); // dueño: $this->asesor
        $doc = DocumentoExpediente::create([
            'expediente_id' => $exp->id,
            'tipo'          => 'ine',
            'nombre'        => 'INE',
            'seccion'       => 'acreditado',
            'estado'        => 'recibido',
            'ruta_archivo'  => 'expedientes/1/docs/ine.jpg',
        ]);

        $this->actingAs($this->asesor, 'sanctum')   // incluso siendo el dueño del expediente
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", [
                'motivo' => 'Intento no autorizado',
            ])
            ->assertStatus(403);
        $this->actingAs($this->asesor, 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/validar")
            ->assertStatus(403);

        $this->assertDatabaseHas('documentos_expediente', ['id' => $doc->id, 'estado' => 'recibido']);
    }

    public function test_rechazar_documento_no_notifica_si_expediente_sin_acreditado_vinculado(): void
    {
        Notification::fake();

        // Sin acreditado_id — el acreditado no recibe nada; solo se avisa al asesor del expediente
        $exp = $this->crearExpediente();
        $doc = DocumentoExpediente::create([
            'expediente_id' => $exp->id,
            'tipo'          => 'ine',
            'nombre'        => 'INE',
            'seccion'       => 'acreditado',
            'estado'        => 'recibido',
            'ruta_archivo'  => 'expedientes/1/docs/ine.jpg',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", ['motivo' => 'Sin acreditado vinculado'])
            ->assertStatus(200);

        Notification::assertSentTo($this->asesor, DocumentoRechazado::class);
        Notification::assertCount(1);
    }

    public function test_admin_valida_un_documento_y_reemplazarlo_lo_regresa_a_revision(): void
    {
        $exp = $this->crearExpediente();
        $doc = DocumentoExpediente::create([
            'expediente_id' => $exp->id, 'tipo' => 'ine', 'nombre' => 'INE', 'seccion' => 'acreditado',
            'estado' => 'recibido', 'ruta_archivo' => 'expedientes/1/docs/ine.jpg', 'notas' => 'INE del dueño',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/validar")
            ->assertOk()
            ->assertJsonPath('data.validacion', 'validado');

        $this->assertNotNull($doc->fresh()->validado_at);

        // Rechazar conserva la nota que identifica el documento
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", ['motivo' => 'Vencida'])
            ->assertOk()
            ->assertJsonPath('data.validacion', 'rechazado');

        $doc->refresh();
        $this->assertSame('INE del dueño', $doc->notas);
        $this->assertSame('Vencida', $doc->motivo_rechazo);
        $this->assertNull($doc->validado_at);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');

        return $admin;
    }

    // ─── Cambio de etapa notifica también al acreditado vinculado ─────────

    // ─── PushService no cruza tokens entre User y Acreditado ───────────────

    public function test_pushservice_envia_solo_al_token_del_acreditado_no_al_de_un_user_con_mismo_id(): void
    {
        // Sin 'id' en la respuesta para que PushService no dispare el chequeo
        // de receipt (ese segundo request hace sleep(3) — no queremos eso en el test)
        Http::fake(['exp.host/*' => Http::response(['data' => ['status' => 'ok']])]);

        // $this->asesor ya existe con id=1 (users) — el primer Acreditado
        // creado en un test también arranca en id=1 (tabla independiente):
        // colisión de IDs entre las dos tablas, a propósito.
        $acreditado = $this->crearAcreditado();
        $this->assertSame($this->asesor->id, $acreditado->id, 'Precondición del test: IDs deben coincidir entre users y acreditados');

        DeviceToken::create([
            'tokenable_type' => User::class,
            'tokenable_id'   => $this->asesor->id,
            'user_id'        => $this->asesor->id,
            'fcm_token'      => 'ExponentPushToken[del-user]',
            'plataforma'     => 'android',
        ]);
        DeviceToken::create([
            'tokenable_type' => Acreditado::class,
            'tokenable_id'   => $acreditado->id,
            'user_id'        => null,
            'fcm_token'      => 'ExponentPushToken[del-acreditado]',
            'plataforma'     => 'ios',
        ]);

        PushService::sendToUser($acreditado, 'Título', 'Cuerpo');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === 'ExponentPushToken[del-acreditado]');
    }
}
