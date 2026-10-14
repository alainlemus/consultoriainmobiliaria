<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\DocumentoSubido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Recorrido completo de los documentos de un expediente, como asesor y como acreditado:
 * prospecto → expediente → subir → revisar/validar → resubir → completo.
 */
class FlujoDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private User $otroAsesor;
    private TipoTramite $tipo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        foreach (['super_admin', 'asesor'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
        $this->admin      = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        $this->asesor     = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');
        $this->otroAsesor = User::factory()->create(['activo' => true]);
        $this->otroAsesor->assignRole('asesor');
        $this->tipo       = TipoTramite::create(['nombre' => 'Crédito Tradicional FOVISSSTE', 'slug' => 'fov-flujo', 'porcentaje_honorarios' => 8]);
    }

    private function pdf(string $n = 'doc.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($n, 40, 'application/pdf');
    }

    // ── Asesor ───────────────────────────────────────────────────────────────

    public function test_asesor_prospecto_a_expediente_subir_validar_y_completar(): void
    {
        $contacto = Contacto::create(['nombre' => 'Ana Prospecto', 'telefono' => '5511111111', 'email' => 'ana@example.com', 'curp' => 'AAAA800101HHGRRN01', 'asesor_id' => $this->asesor->id]);

        // 1. Inicia el expediente del prospecto (y un segundo toque no crea otro)
        $r = $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/expedientes', ['contacto_id' => $contacto->id, 'tipo_tramite_id' => $this->tipo->id])
            ->assertCreated();
        $expId = $r->json('data.id');
        $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/expedientes', ['contacto_id' => $contacto->id, 'tipo_tramite_id' => $this->tipo->id])
            ->assertOk()
            ->assertJsonPath('data.id', $expId);
        $this->assertSame(1, Expediente::where('contacto_id', $contacto->id)->count());

        // Guarda los datos del prospecto para ligarlo luego con su cuenta
        $exp = Expediente::find($expId);
        $this->assertSame('ana@example.com', $exp->acreditado_email);
        $this->assertSame('AAAA800101HHGRRN01', $exp->acreditado_curp);

        // 2. Sube dos documentos del mismo tipo: el segundo queda como "INE (2)"
        foreach (['uno', 'dos'] as $n) {
            $this->actingAs($this->asesor, 'sanctum')
                ->post("/api/v1/expedientes/{$expId}/documentos", ['archivo' => $this->pdf("$n.pdf"), 'tipo_documento' => 'INE', 'notas' => "INE $n"], ['Accept' => 'application/json'])
                ->assertCreated();
        }
        $this->assertEqualsCanonicalizing(['INE', 'INE (2)'], $exp->documentos()->pluck('tipo')->all());
        Notification::assertSentTo($this->admin, DocumentoSubido::class);

        // 3. Los ve en su lista; otro asesor no tiene acceso
        $this->actingAs($this->asesor, 'sanctum')->getJson("/api/v1/expedientes/{$expId}/documentos")->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($this->otroAsesor, 'sanctum')->getJson("/api/v1/expedientes/{$expId}/documentos")->assertForbidden();

        // 4. El asesor no valida; el admin sí, y con todo validado el expediente queda completo
        $docs = $exp->documentos()->get();
        $this->actingAs($this->asesor, 'sanctum')->postJson("/api/v1/expedientes/{$expId}/documentos/{$docs[0]->id}/validar")->assertForbidden();
        $this->assertFalse($exp->estaCompleto());
        foreach ($docs as $d) {
            $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$expId}/documentos/{$d->id}/validar")->assertOk();
        }
        $this->assertTrue($exp->fresh()->estaCompleto());

        // 5. Reemplazar un documento validado lo regresa a revisión y el expediente deja de estar completo
        $this->actingAs($this->asesor, 'sanctum')
            ->post("/api/v1/expedientes/{$expId}/documentos/{$docs[0]->id}/reemplazar", ['archivo' => $this->pdf('nuevo.pdf')], ['Accept' => 'application/json'])
            ->assertOk();
        $this->assertSame('en_revision', $docs[0]->fresh()->validacion);
        $this->assertFalse($exp->fresh()->estaCompleto());
    }

    public function test_el_asesor_obtiene_el_enlace_para_ver_un_documento(): void
    {
        $exp = Expediente::create(['asesor_id' => $this->asesor->id, 'tipo_tramite_id' => $this->tipo->id, 'estado' => 'en_proceso', 'acreditado_nombre' => 'X']);
        $this->actingAs($this->asesor, 'sanctum')
            ->post("/api/v1/expedientes/{$exp->id}/documentos", ['archivo' => $this->pdf(), 'tipo_documento' => 'CURP'], ['Accept' => 'application/json'])
            ->assertCreated();
        $doc = $exp->documentos()->first();

        $this->actingAs($this->asesor, 'sanctum')
            ->getJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/ver")
            ->assertOk()
            ->assertJsonStructure(['url']);
    }

    // ── Acreditado ───────────────────────────────────────────────────────────

    public function test_acreditado_ve_su_expediente_sube_y_resube_documentos(): void
    {
        // El asesor abre el expediente del prospecto y después el cliente se registra con el mismo correo
        $contacto = Contacto::create(['nombre' => 'Beto', 'telefono' => '5522222222', 'email' => 'beto@example.com', 'asesor_id' => $this->asesor->id]);
        $this->actingAs($this->asesor, 'sanctum')->postJson('/api/v1/expedientes', ['contacto_id' => $contacto->id, 'tipo_tramite_id' => $this->tipo->id])->assertCreated();
        $acreditado = Acreditado::create(['name' => 'Beto', 'email' => 'beto@example.com', 'password' => Hash::make('x12345678'), 'activo' => true]);

        $this->actingAs($acreditado, 'sanctum')->getJson('/api/v1/acreditado/expediente')->assertOk()->assertJsonPath('data.estado', 'en_revision');
        $exp = $acreditado->expedientes()->first();
        $this->assertNotNull($exp, 'el expediente se liga por correo');

        // Sube un documento con nota
        $this->actingAs($acreditado, 'sanctum')
            ->post('/api/v1/acreditado/expediente/documentos', ['archivo' => $this->pdf('a.pdf'), 'tipo_documento' => 'CURP', 'notas' => 'mi CURP'], ['Accept' => 'application/json'])
            ->assertCreated();
        $doc = $exp->documentos()->where('tipo', 'CURP')->first();
        $this->assertSame('recibido', $doc->estado);
        $this->assertSame('mi CURP', $doc->notas);
        $archivoViejo = $doc->ruta_archivo;
        Storage::disk('local')->assertExists($archivoViejo);

        // Lo ve en su lista
        $this->actingAs($acreditado, 'sanctum')->getJson('/api/v1/acreditado/expediente/documentos')->assertOk()->assertJsonFragment(['nombre' => 'CURP']);

        // Los 3 talones: cada subida es un documento nuevo, no se pisan entre sí
        foreach (['t1', 't2', 't3'] as $n) {
            $this->actingAs($acreditado, 'sanctum')
                ->post('/api/v1/acreditado/expediente/documentos', ['archivo' => $this->pdf("$n.pdf"), 'tipo_documento' => 'Talón de pago'], ['Accept' => 'application/json'])
                ->assertCreated();
        }
        $this->assertEqualsCanonicalizing(['Talón de pago', 'Talón de pago (2)', 'Talón de pago (3)'], $exp->documentos()->where('tipo', 'like', 'Talón%')->pluck('tipo')->all());

        // El admin rechaza la CURP → el acreditado la vuelve a subir: se reemplaza (no se duplica),
        // regresa a revisión y se borra el archivo viejo
        $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/rechazar", ['motivo' => 'Borrosa'])->assertOk();
        $this->assertSame('rechazado', $doc->fresh()->validacion);

        $this->actingAs($acreditado, 'sanctum')
            ->post('/api/v1/acreditado/expediente/documentos', ['archivo' => $this->pdf('b.pdf'), 'tipo_documento' => 'CURP'], ['Accept' => 'application/json'])
            ->assertCreated();
        $doc->refresh();
        $this->assertSame('en_revision', $doc->validacion);
        $this->assertNull($doc->motivo_rechazo);
        $this->assertNotSame($archivoViejo, $doc->ruta_archivo);
        Storage::disk('local')->assertMissing($archivoViejo);
        $this->assertSame(1, $exp->documentos()->where('tipo', 'CURP')->count());

        // Con todo validado el expediente está completo; un documento nuevo lo vuelve a abrir
        foreach ($exp->documentos()->get() as $d) {
            $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$d->id}/validar")->assertOk();
        }
        $this->assertTrue($exp->fresh()->estaCompleto());
        $this->actingAs($acreditado, 'sanctum')
            ->post('/api/v1/acreditado/expediente/documentos', ['archivo' => $this->pdf('c.pdf'), 'tipo_documento' => 'CURP'], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertFalse($exp->fresh()->estaCompleto());
        $this->assertSame(2, $exp->documentos()->where('tipo', 'like', 'CURP%')->count());
    }

    public function test_el_expediente_iniciado_sin_senal_se_crea_al_sincronizar_sin_duplicarse(): void
    {
        $contacto = Contacto::create(['nombre' => 'Carla', 'telefono' => '5533333333', 'email' => 'carla@example.com', 'asesor_id' => $this->asesor->id]);
        $op = fn (string $id) => ['id_local' => $id, 'tipo' => 'crear_expediente', 'datos' => ['contacto_id' => $contacto->id, 'tipo_tramite_id' => $this->tipo->id]];

        // El mismo expediente encolado dos veces (doble toque sin señal) se crea una sola vez
        $r = $this->actingAs($this->asesor, 'sanctum')
            ->postJson('/api/v1/sync', ['operaciones' => [$op('a'), $op('b')]])
            ->assertOk();

        $this->assertSame(['ok', 'ok'], collect($r->json('resultados'))->pluck('estado')->all());
        $this->assertSame(1, Expediente::where('contacto_id', $contacto->id)->count());
        $this->assertSame('carla@example.com', Expediente::where('contacto_id', $contacto->id)->first()->acreditado_email);
    }

    public function test_el_acreditado_solo_ve_los_documentos_de_su_seccion_y_no_se_duplican_los_del_asesor(): void
    {
        $acreditado = Acreditado::create(['name' => 'D', 'email' => 'd@example.com', 'password' => Hash::make('x12345678'), 'activo' => true]);
        $exp = Expediente::create(['asesor_id' => $this->asesor->id, 'tipo_tramite_id' => $this->tipo->id, 'acreditado_id' => $acreditado->id, 'estado' => 'en_proceso', 'acreditado_nombre' => 'D']);
        // El asesor ya subió la INE (sin categoría) y un documento del vendedor
        $exp->documentos()->create(['tipo' => 'INE', 'nombre' => 'INE', 'seccion' => 'acreditado', 'estado' => 'recibido', 'ruta_archivo' => 'x/ine.pdf']);
        $exp->documentos()->create(['tipo' => 'Escritura', 'nombre' => 'Escritura', 'seccion' => 'vendedor', 'estado' => 'recibido', 'ruta_archivo' => 'x/esc.pdf']);

        $this->actingAs($acreditado, 'sanctum')
            ->getJson('/api/v1/acreditado/expediente/documentos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'INE');

        // Si el acreditado sube su INE no queda otra "INE" igual: se numera
        $this->actingAs($acreditado, 'sanctum')
            ->post('/api/v1/acreditado/expediente/documentos', ['archivo' => $this->pdf(), 'tipo_documento' => 'INE'], ['Accept' => 'application/json'])
            ->assertCreated();
        $this->assertEqualsCanonicalizing(['INE', 'INE (2)'], $exp->documentos()->where('seccion', 'acreditado')->pluck('tipo')->all());
    }

    public function test_acreditado_sin_expediente_no_puede_subir(): void
    {
        $acreditado = Acreditado::create(['name' => 'Sin Exp', 'email' => 'sin@example.com', 'password' => Hash::make('x12345678'), 'activo' => true]);

        $this->actingAs($acreditado, 'sanctum')
            ->post('/api/v1/acreditado/expediente/documentos', ['archivo' => $this->pdf(), 'tipo_documento' => 'CURP'], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_acreditado_rechaza_archivos_no_permitidos(): void
    {
        $acreditado = Acreditado::create(['name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('x12345678'), 'activo' => true]);
        Expediente::create(['asesor_id' => $this->asesor->id, 'tipo_tramite_id' => $this->tipo->id, 'acreditado_id' => $acreditado->id, 'estado' => 'en_proceso', 'acreditado_nombre' => 'A']);

        $this->actingAs($acreditado, 'sanctum')
            ->post('/api/v1/acreditado/expediente/documentos', ['archivo' => UploadedFile::fake()->create('virus.exe', 10), 'tipo_documento' => 'CURP'], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }
}
