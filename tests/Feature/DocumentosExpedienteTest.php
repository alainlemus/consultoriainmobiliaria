<?php

namespace Tests\Feature;

use App\Jobs\ExtractPdfDataJob;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentosExpedienteTest extends TestCase
{
    use RefreshDatabase;

    private function asesor(): User
    {
        Role::findOrCreate('asesor', 'web');
        Role::findOrCreate('super_admin', 'web');
        $u = User::factory()->create();
        $u->assignRole('asesor');
        Sanctum::actingAs($u);

        return $u;
    }

    private function tramite(): array
    {
        $tipo  = \App\Models\TipoTramite::firstOrCreate(['slug' => 'fovissste'], ['nombre' => 'FOVISSSTE']);
        $etapa = \App\Models\EtapaTramite::firstOrCreate(['tipo_tramite_id' => $tipo->id, 'orden' => 1], ['nombre' => 'Inicio']);

        return [$tipo->id, $etapa->id];
    }

    private function expediente(User $asesor): Expediente
    {
        [$tipoId, $etapaId] = $this->tramite();
        $contacto = Contacto::create(['nombre' => 'Ana Prueba', 'asesor_id' => $asesor->id]);

        return Expediente::create([
            'contacto_id' => $contacto->id, 'asesor_id' => $asesor->id, 'tipo_tramite_id' => $tipoId,
            'etapa_tramite_id' => $etapaId, 'acreditado_nombre' => 'Ana Prueba',
        ]);
    }

    public function test_los_documentos_del_mismo_tipo_se_acumulan_y_llegan_por_capturar(): void
    {
        Storage::fake('local');
        Queue::fake();
        $exp = $this->expediente($this->asesor());

        foreach (['a.pdf', 'b.pdf'] as $nombre) {
            $this->postJson("/api/v1/expedientes/{$exp->id}/documentos", [
                'archivo'        => UploadedFile::fake()->create($nombre, 100, 'application/pdf'),
                'tipo_documento' => 'INE',
            ])->assertCreated();
        }

        $this->assertSame(['INE', 'INE (2)'], $exp->documentos()->orderBy('id')->pluck('tipo')->all());
        $this->assertSame(2, $exp->documentos()->where('estado', 'recibido')->whereNull('capturado_at')->count());
        Queue::assertPushed(ExtractPdfDataJob::class, 1); // una sola corrida de OCR por tanda de subidas
    }

    public function test_con_seccion_se_reemplaza_el_documento_del_checklist(): void
    {
        Storage::fake('local');
        Queue::fake();
        $exp = $this->expediente($this->asesor());

        foreach (['a.pdf', 'b.pdf'] as $nombre) {
            $this->postJson("/api/v1/expedientes/{$exp->id}/documentos", [
                'archivo'        => UploadedFile::fake()->create($nombre, 100, 'application/pdf'),
                'tipo_documento' => 'INE con QR',
                'seccion'        => 'acreditado',
            ])->assertCreated();
        }

        $this->assertSame(1, $exp->documentos()->count());
    }

    public function test_un_asesor_no_puede_subir_documentos_a_expedientes_ajenos(): void
    {
        $this->asesor();
        $otro = User::factory()->create();
        $ajeno = $this->expediente($otro);

        $this->postJson("/api/v1/expedientes/{$ajeno->id}/documentos", [
            'archivo'        => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            'tipo_documento' => 'INE',
        ])->assertForbidden();
    }

    public function test_el_listado_de_prospectos_oculta_a_los_que_ya_tienen_expediente(): void
    {
        $asesor = $this->asesor();
        $this->expediente($asesor);
        $libre = Contacto::create(['nombre' => 'Aun prospecto', 'asesor_id' => $asesor->id]);

        $this->getJson('/api/v1/contactos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $libre->id);
    }

    public function test_al_reemplazar_un_documento_vuelve_a_quedar_por_capturar(): void
    {
        Storage::fake('local');
        Queue::fake();
        $exp = $this->expediente($this->asesor());

        $this->postJson("/api/v1/expedientes/{$exp->id}/documentos", [
            'archivo'        => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            'tipo_documento' => 'Estado de cuenta',
        ])->assertCreated();

        $doc = $exp->documentos()->first();
        $doc->update(['capturado_at' => now()]);

        $this->postJson("/api/v1/expedientes/{$exp->id}/documentos/{$doc->id}/reemplazar", [
            'archivo' => UploadedFile::fake()->create('nuevo.pdf', 100, 'application/pdf'),
        ])->assertOk();

        $this->assertNull($doc->fresh()->capturado_at);
        $this->assertSame('recibido', $doc->fresh()->estado);
    }
}
