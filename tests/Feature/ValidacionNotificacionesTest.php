<?php

namespace Tests\Feature;

use App\Mail\CarpetaExpedienteMail;
use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\DocumentoRechazado;
use App\Notifications\DocumentoSubido;
use App\Notifications\DocumentoValidado;
use App\Notifications\ExpedienteCompleto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ValidacionNotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private Expediente $exp;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('asesor', 'web');

        $this->admin  = User::factory()->create(['activo' => true]);
        $this->asesor = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        $this->asesor->assignRole('asesor');

        $tipo  = TipoTramite::create(['nombre' => 'FOVISSSTE', 'slug' => 'fovissste']);
        $etapa = EtapaTramite::create(['tipo_tramite_id' => $tipo->id, 'nombre' => 'Inicio', 'orden' => 1]);
        $contacto = Contacto::create(['nombre' => 'Ana Prueba', 'asesor_id' => $this->asesor->id]);

        $this->exp = Expediente::create([
            'contacto_id' => $contacto->id, 'asesor_id' => $this->asesor->id, 'tipo_tramite_id' => $tipo->id,
            'etapa_tramite_id' => $etapa->id, 'acreditado_nombre' => 'Ana Prueba',
        ]);
    }

    private function doc(string $tipo): DocumentoExpediente
    {
        return $this->exp->documentos()->create([
            'tipo' => $tipo, 'nombre' => $tipo, 'seccion' => 'acreditado', 'estado' => 'recibido',
            'ruta_archivo' => "expedientes/{$this->exp->id}/docs/{$tipo}.pdf",
        ]);
    }

    private function validar(DocumentoExpediente $doc)
    {
        return $this->postJson("/api/v1/expedientes/{$this->exp->id}/documentos/{$doc->id}/validar");
    }

    public function test_al_validar_se_avisa_al_asesor_y_al_validar_el_ultimo_el_expediente_queda_completo(): void
    {
        Notification::fake();
        $ine  = $this->doc('INE');
        $curp = $this->doc('CURP');

        Sanctum::actingAs($this->admin);

        $this->validar($ine)->assertOk();
        Notification::assertSentTo($this->asesor, DocumentoValidado::class, fn ($n) => $n->documento->is($ine));
        Notification::assertNotSentTo($this->asesor, ExpedienteCompleto::class);

        $this->validar($curp)->assertOk();
        Notification::assertSentTo($this->asesor, ExpedienteCompleto::class, 1);

        // Revalidar no vuelve a avisar
        $this->validar($curp)->assertOk();
        Notification::assertSentToTimes($this->asesor, DocumentoValidado::class, 2);
        Notification::assertSentToTimes($this->asesor, ExpedienteCompleto::class, 1);
    }

    public function test_al_rechazar_se_avisa_al_asesor(): void
    {
        Notification::fake();
        $ine = $this->doc('INE');

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/documentos/{$ine->id}/rechazar", ['motivo' => 'Vencida'])->assertOk();

        Notification::assertSentTo($this->asesor, DocumentoRechazado::class, fn ($n) => $n->motivo === 'Vencida');
    }

    public function test_los_expedientes_completos_ya_no_salen_en_la_app(): void
    {
        $ine = $this->doc('INE');
        Sanctum::actingAs($this->asesor);

        // Con un documento por validar sigue visible
        $this->getJson('/api/v1/expedientes')->assertOk()->assertJsonCount(1, 'data');

        $ine->update(['validado_at' => now()]);
        $this->getJson('/api/v1/expedientes')->assertOk()->assertJsonCount(0, 'data');

        // Subir otro documento lo regresa a la lista hasta que también se valide
        $this->doc('CURP');
        $this->getJson('/api/v1/expedientes')->assertOk()->assertJsonCount(1, 'data');

        // El asesor ya no lo ve cuando está completo; el administrador sí (puede pedir un documento faltante)
        $this->exp->documentos()->update(['validado_at' => now()]);
        $this->getJson('/api/v1/expedientes')->assertOk()->assertJsonCount(0, 'data');
        Sanctum::actingAs($this->admin);
        $this->getJson('/api/v1/expedientes')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_un_expediente_sin_documentos_sigue_visible(): void
    {
        Sanctum::actingAs($this->asesor);
        $this->getJson('/api/v1/expedientes')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_al_subir_un_documento_se_avisa_a_los_administradores(): void
    {
        Storage::fake('local');
        Queue::fake();
        Notification::fake();

        Sanctum::actingAs($this->asesor);
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/documentos", [
            'archivo'        => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            'tipo_documento' => 'INE',
        ])->assertCreated();

        Notification::assertSentTo($this->admin, DocumentoSubido::class);
    }

    public function test_el_admin_envia_la_carpeta_a_su_correo_y_el_enlace_descarga_el_zip(): void
    {
        Storage::fake('local');
        Mail::fake();

        Storage::disk('local')->put("expedientes/{$this->exp->id}/docs/INE.pdf", 'contenido');
        $this->doc('INE');

        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/enviar-carpeta")
            ->assertOk()
            ->assertJsonPath('email', $this->admin->email);

        Mail::assertSent(CarpetaExpedienteMail::class, function (CarpetaExpedienteMail $mail) {
            return $mail->hasTo($this->admin->email) && str_contains($mail->enlace, 'signature=');
        });

        $enlace = URL::temporarySignedRoute('expediente.carpeta.firmada', now()->addHour(), ['expediente' => $this->exp->id]);
        $this->get($enlace)->assertOk()->assertHeader('content-type', 'application/zip');

        // Sin firma no se descarga
        $this->get(route('expediente.carpeta.firmada', ['expediente' => $this->exp->id]))->assertForbidden();
    }

    public function test_un_asesor_no_puede_enviar_la_carpeta(): void
    {
        Mail::fake();
        $this->doc('INE');

        Sanctum::actingAs($this->asesor);
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/enviar-carpeta")->assertForbidden();
        Mail::assertNothingSent();
    }

    public function test_no_se_envia_una_carpeta_vacia(): void
    {
        Sanctum::actingAs($this->admin);
        $this->postJson("/api/v1/expedientes/{$this->exp->id}/enviar-carpeta")->assertStatus(422);
    }
}
