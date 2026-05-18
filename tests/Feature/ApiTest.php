<?php

namespace Tests\Feature;

use App\Models\Contacto;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $asesor;
    private User $otroAsesor;
    private User $superAdmin;
    private TipoTramite $tipoTramite;
    private EtapaTramite $etapaTramite;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        $rolAsesor     = Role::firstOrCreate(['name' => 'asesor',       'guard_name' => 'web']);
        $rolSuperAdmin = Role::firstOrCreate(['name' => 'super_admin',  'guard_name' => 'web']);

        $this->asesor     = User::factory()->create(['activo' => true]);
        $this->otroAsesor = User::factory()->create(['activo' => true]);
        $this->superAdmin = User::factory()->create(['activo' => true]);

        $this->asesor->assignRole($rolAsesor);
        $this->otroAsesor->assignRole($rolAsesor);
        $this->superAdmin->assignRole($rolSuperAdmin);

        $this->tipoTramite = TipoTramite::create([
            'nombre'                => 'Crédito Tradicional FOVISSSTE',
            'slug'                  => 'tradicional',
            'porcentaje_honorarios' => 5.00,
            'activo'                => true,
            'orden'                 => 1,
        ]);

        $this->etapaTramite = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipoTramite->id,
            'nombre'          => 'Inicio',
            'orden'           => 1,
            'es_final'        => false,
        ]);
    }

    // ── Auth ──────────────────────────────────────────────────────────────

    public function test_login_exitoso(): void
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->asesor->email,
            'password' => 'password',
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles']]);
    }

    public function test_login_credenciales_invalidas(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email'    => $this->asesor->email,
            'password' => 'incorrecta',
        ])->assertStatus(422);
    }

    public function test_login_cuenta_inactiva(): void
    {
        $inactivo = User::factory()->create(['activo' => false]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $inactivo->email,
            'password' => 'password',
        ])->assertStatus(403);
    }

    public function test_me_retorna_usuario_autenticado(): void
    {
        $this->actingAs($this->asesor, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', $this->asesor->email);
    }

    public function test_logout_elimina_token(): void
    {
        $token = $this->asesor->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        // El token debe haberse eliminado de la base de datos
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_rutas_protegidas_sin_token_retornan_401(): void
    {
        $this->getJson('/api/v1/contactos')->assertStatus(401);
        $this->getJson('/api/v1/expedientes')->assertStatus(401);
    }

    // ── Contactos ─────────────────────────────────────────────────────────

    public function test_asesor_puede_crear_contacto(): void
    {
        $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/contactos', [
                'nombre'           => 'Juan',
                'apellido_paterno' => 'Pérez',
                'telefono'         => '5512345678',
                'email'            => 'juan@ejemplo.com',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.nombre', 'Juan')
            ->assertJsonPath('data.asesor_id', $this->asesor->id);
    }

    public function test_asesor_solo_ve_sus_contactos(): void
    {
        Contacto::create(['nombre' => 'Mío', 'apellido_paterno' => 'X', 'asesor_id' => $this->asesor->id,     'estado_prospecto' => 'nuevo']);
        Contacto::create(['nombre' => 'Otro', 'apellido_paterno' => 'Y', 'asesor_id' => $this->otroAsesor->id, 'estado_prospecto' => 'nuevo']);

        $res = $this->actingAs($this->asesor, 'sanctum')
            ->getJson('/api/v1/contactos');

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('Mío', $res->json('data.0.nombre'));
    }

    public function test_super_admin_ve_todos_los_contactos(): void
    {
        Contacto::create(['nombre' => 'A', 'apellido_paterno' => 'X', 'asesor_id' => $this->asesor->id,     'estado_prospecto' => 'nuevo']);
        Contacto::create(['nombre' => 'B', 'apellido_paterno' => 'Y', 'asesor_id' => $this->otroAsesor->id, 'estado_prospecto' => 'nuevo']);

        $res = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v1/contactos');

        $res->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($res->json('data')));
    }

    public function test_asesor_no_puede_ver_contacto_ajeno(): void
    {
        $ajeno = Contacto::create(['nombre' => 'Ajeno', 'apellido_paterno' => 'Z', 'asesor_id' => $this->otroAsesor->id, 'estado_prospecto' => 'nuevo']);

        $this->actingAs($this->asesor, 'sanctum')
            ->getJson("/api/v1/contactos/{$ajeno->id}")
            ->assertStatus(403);
    }

    public function test_busqueda_contactos_por_nombre(): void
    {
        Contacto::create(['nombre' => 'Carlos', 'apellido_paterno' => 'G', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'nuevo']);
        Contacto::create(['nombre' => 'Luisa',  'apellido_paterno' => 'G', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'nuevo']);

        $res = $this->actingAs($this->asesor, 'sanctum')
            ->getJson('/api/v1/contactos?q=Carlos');

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_filtro_contactos_por_estado(): void
    {
        Contacto::create(['nombre' => 'Nuevo', 'apellido_paterno' => 'X', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'nuevo']);
        Contacto::create(['nombre' => 'Cerrado', 'apellido_paterno' => 'X', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'cerrado']);

        $res = $this->actingAs($this->asesor, 'sanctum')
            ->getJson('/api/v1/contactos?estado=cerrado');

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals('cerrado', $res->json('data.0.estado_prospecto'));
    }

    public function test_actualizar_estado_contacto(): void
    {
        $contacto = Contacto::create(['nombre' => 'Test', 'apellido_paterno' => 'U', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'nuevo']);

        $this->actingAs($this->asesor, 'sanctum')
            ->putJson("/api/v1/contactos/{$contacto->id}", ['estado_prospecto' => 'contactado'])
            ->assertStatus(200)
            ->assertJsonPath('data.estado_prospecto', 'contactado');
    }

    // ── Expedientes ───────────────────────────────────────────────────────

    public function test_asesor_puede_crear_expediente(): void
    {
        $contacto = Contacto::create(['nombre' => 'Test', 'apellido_paterno' => 'E', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'nuevo']);

        $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/expedientes', [
                'contacto_id'     => $contacto->id,
                'tipo_tramite_id' => $this->tipoTramite->id,
                'monto_credito'   => 500000,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.asesor_id', $this->asesor->id)
            ->assertJsonStructure(['data' => ['id', 'folio', 'estado']]);
    }

    public function test_folio_se_genera_automaticamente(): void
    {
        $contacto = Contacto::create(['nombre' => 'Folio', 'apellido_paterno' => 'T', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'nuevo']);

        $res = $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/expedientes', [
                'contacto_id'     => $contacto->id,
                'tipo_tramite_id' => $this->tipoTramite->id,
            ]);

        $res->assertStatus(201);
        $this->assertStringStartsWith('EXP-', $res->json('data.folio'));
    }

    public function test_asesor_no_puede_ver_expediente_ajeno(): void
    {
        $contacto = Contacto::create(['nombre' => 'X', 'apellido_paterno' => 'X', 'asesor_id' => $this->otroAsesor->id, 'estado_prospecto' => 'nuevo']);
        $exp = Expediente::create(['contacto_id' => $contacto->id, 'tipo_tramite_id' => $this->tipoTramite->id, 'etapa_tramite_id' => $this->etapaTramite->id, 'asesor_id' => $this->otroAsesor->id, 'estado' => 'en_proceso']);

        $this->actingAs($this->asesor, 'sanctum')
            ->getJson("/api/v1/expedientes/{$exp->id}")
            ->assertStatus(403);
    }

    // ── Ubicaciones ───────────────────────────────────────────────────────

    public function test_registrar_ubicacion_gps(): void
    {
        $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/ubicaciones', [
                'latitud'  => 19.4326,
                'longitud' => -99.1332,
                'tipo'     => 'visita_cliente',
                'notas'    => 'Visita inicial',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.latitud', 19.4326);
    }

    public function test_mapa_devuelve_ubicaciones_del_asesor(): void
    {
        \App\Models\Ubicacion::create(['user_id' => $this->asesor->id,     'latitud' => 19.4, 'longitud' => -99.1, 'tipo' => 'visita_cliente', 'visitado_en' => now()]);
        \App\Models\Ubicacion::create(['user_id' => $this->otroAsesor->id, 'latitud' => 20.0, 'longitud' => -98.0, 'tipo' => 'visita_cliente', 'visitado_en' => now()]);

        $res = $this->actingAs($this->asesor, 'sanctum')
            ->getJson('/api/v1/ubicaciones/mapa');

        $res->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
    }

    // ── Documentos ────────────────────────────────────────────────────────

    public function test_subir_documento_a_expediente(): void
    {
        Storage::fake('public');

        $contacto = Contacto::create(['nombre' => 'Doc', 'apellido_paterno' => 'T', 'asesor_id' => $this->asesor->id, 'estado_prospecto' => 'nuevo']);
        $exp = Expediente::create(['contacto_id' => $contacto->id, 'tipo_tramite_id' => $this->tipoTramite->id, 'etapa_tramite_id' => $this->etapaTramite->id, 'asesor_id' => $this->asesor->id, 'estado' => 'en_proceso']);

        $this->actingAs($this->asesor, 'sanctum')
            ->postJson("/api/v1/expedientes/{$exp->id}/documentos", [
                'archivo'        => UploadedFile::fake()->create('identificacion.pdf', 200, 'application/pdf'),
                'tipo_documento' => 'identificacion_oficial',
                'notas'          => 'INE vigente',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.tipo', 'identificacion_oficial')
            ->assertJsonPath('data.estado', 'pendiente');
    }

    // ── Sync batch ────────────────────────────────────────────────────────

    public function test_sync_batch_crea_contacto(): void
    {
        $res = $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/sync', [
                'operaciones' => [[
                    'id_local'  => 'uuid-test-001',
                    'tipo'      => 'crear_contacto',
                    'datos'     => [
                        'nombre'           => 'Offline',
                        'apellido_paterno' => 'Test',
                        'telefono'         => '5500000000',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('resultados.0.estado', 'ok')
            ->assertJsonPath('resultados.0.id_local', 'uuid-test-001');

        $this->assertDatabaseHas('contactos', ['nombre' => 'Offline', 'asesor_id' => $this->asesor->id]);
    }

    public function test_sync_batch_tipo_desconocido_retorna_error(): void
    {
        $res = $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/sync', [
                'operaciones' => [[
                    'id_local' => 'uuid-fail',
                    'tipo'     => 'tipo_inexistente',
                    'datos'    => [],
                ]],
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('resultados.0.estado', 'error');
    }

    // ── Dispositivos FCM ──────────────────────────────────────────────────

    public function test_registrar_dispositivo_fcm(): void
    {
        $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/dispositivos', [
                'fcm_token'  => 'token-fcm-abc123',
                'plataforma' => 'android',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('device_tokens', [
            'user_id'   => $this->asesor->id,
            'fcm_token' => 'token-fcm-abc123',
        ]);
    }
}
