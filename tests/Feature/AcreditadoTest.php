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

    public function test_asesor_puede_rechazar_documento_con_motivo(): void
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

        $this->actingAs($this->asesor, 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", [
                'motivo' => 'La foto sale borrosa, no se alcanza a leer la CURP',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.estado', 'rechazado');

        $this->assertDatabaseHas('documentos_expediente', [
            'id'     => $doc->id,
            'estado' => 'rechazado',
            'notas'  => 'La foto sale borrosa, no se alcanza a leer la CURP',
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

        $this->actingAs($this->asesor, 'sanctum')
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

        $this->actingAs($this->asesor, 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", [
                'motivo' => 'No hay nada que rechazar',
            ])
            ->assertStatus(422);
    }

    public function test_otro_asesor_no_puede_rechazar_documento_ajeno(): void
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

        $this->actingAs($this->otroAsesor, 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", [
                'motivo' => 'Intento no autorizado',
            ])
            ->assertStatus(403);

        $this->assertDatabaseHas('documentos_expediente', ['id' => $doc->id, 'estado' => 'recibido']);
    }

    public function test_rechazar_documento_no_notifica_si_expediente_sin_acreditado_vinculado(): void
    {
        Notification::fake();

        // Sin acreditado_id — nadie en la app debe recibir el push
        $exp = $this->crearExpediente();
        $doc = DocumentoExpediente::create([
            'expediente_id' => $exp->id,
            'tipo'          => 'ine',
            'nombre'        => 'INE',
            'seccion'       => 'acreditado',
            'estado'        => 'recibido',
            'ruta_archivo'  => 'expedientes/1/docs/ine.jpg',
        ]);

        $this->actingAs($this->asesor, 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", ['motivo' => 'Sin acreditado vinculado'])
            ->assertStatus(200);

        Notification::assertNothingSent();
    }

    // ─── Cambio de etapa notifica también al acreditado vinculado ─────────

    public function test_acreditado_vinculado_recibe_notificacion_de_cambio_de_etapa(): void
    {
        Notification::fake();

        $acreditado = $this->crearAcreditado();
        $exp = $this->crearExpediente(['acreditado_id' => $acreditado->id]);

        $exp->update(['etapa_tramite_id' => $this->etapa2->id]);

        Notification::assertSentTo($this->asesor, EtapaExpedienteCambiada::class);
        Notification::assertSentTo($acreditado, EtapaExpedienteCambiada::class);
    }

    public function test_expediente_sin_acreditado_vinculado_no_falla_al_cambiar_etapa(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente(); // sin acreditado_id
        $exp->update(['etapa_tramite_id' => $this->etapa2->id]);

        Notification::assertSentTo($this->asesor, EtapaExpedienteCambiada::class);
        // No debe reventar ni mandarle nada a ningún Acreditado
        Notification::assertNotSentTo($this->asesor, DocumentoRechazado::class);
    }

    public function test_notificacion_etapa_al_acreditado_no_usa_canal_database(): void
    {
        // El acreditado no tiene bandeja de notificaciones en la app —
        // via() no debe incluir 'database' para ese notifiable (sí para User).
        $acreditado = $this->crearAcreditado();
        $exp = $this->crearExpediente(['acreditado_id' => $acreditado->id]);

        $notif = new EtapaExpedienteCambiada($exp, 'Precalificación', 'Documentación');

        $this->assertNotContains('database', $notif->via($acreditado));
        $this->assertContains('database', $notif->via($this->asesor));
    }

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
