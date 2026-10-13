<?php

namespace Tests\Feature;

use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentosProspectoTest extends TestCase
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

    public function test_subir_documento_crea_el_expediente_y_acumula_documentos_del_mismo_tipo(): void
    {
        Storage::fake('local');
        $asesor   = $this->asesor();
        $contacto = Contacto::create(['nombre' => 'Ana Prueba', 'telefono' => '5512345678', 'asesor_id' => $asesor->id]);

        $this->assertSame(0, Expediente::count());

        foreach (['a.pdf', 'b.pdf'] as $nombre) {
            $this->postJson("/api/v1/contactos/{$contacto->id}/documentos", [
                'archivo'        => UploadedFile::fake()->create($nombre, 100, 'application/pdf'),
                'tipo_documento' => 'INE',
            ])->assertCreated();
        }

        $this->assertSame(1, Expediente::count());
        $exp = Expediente::first();
        $this->assertSame($contacto->id, $exp->contacto_id);
        $this->assertSame($asesor->id, $exp->asesor_id);
        $this->assertSame('Ana Prueba', $exp->acreditado_nombre);
        $this->assertSame(2, $exp->documentos()->count());

        $this->getJson("/api/v1/contactos/{$contacto->id}/documentos")
            ->assertOk()
            ->assertJsonPath('expediente_id', $exp->id)
            ->assertJsonCount(2, 'data');
    }

    public function test_listado_de_prospectos_sigue_mostrando_los_que_ya_tienen_expediente(): void
    {
        Storage::fake('local');
        $asesor   = $this->asesor();
        $contacto = Contacto::create(['nombre' => 'Luis', 'asesor_id' => $asesor->id]);

        $this->postJson("/api/v1/contactos/{$contacto->id}/documentos", [
            'archivo'        => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            'tipo_documento' => 'CURP',
        ])->assertCreated();

        $this->getJson('/api/v1/contactos')
            ->assertOk()
            ->assertJsonPath('data.0.id', $contacto->id)
            ->assertJsonPath('data.0.documentos_count', 1);
    }

    public function test_un_asesor_no_puede_subir_documentos_a_prospectos_ajenos(): void
    {
        $this->asesor();
        $ajeno = Contacto::create(['nombre' => 'Otro', 'asesor_id' => User::factory()->create()->id]);

        $this->postJson("/api/v1/contactos/{$ajeno->id}/documentos", [
            'archivo'        => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            'tipo_documento' => 'INE',
        ])->assertForbidden();
    }
}
