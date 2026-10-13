<?php

namespace Tests\Feature;

use App\Filament\Resources\ExpedienteResource\Pages\EditExpediente;
use App\Filament\Resources\ExpedienteResource\Pages\ListExpedientes;
use App\Filament\Resources\ExpedienteResource\RelationManagers\DocumentosRelationManager;
use App\Models\Contacto;
use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelExpedientesSimplificadoTest extends TestCase
{
    use RefreshDatabase;

    private Expediente $expediente;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');
        foreach (['ViewAny', 'View', 'Create', 'Update'] as $accion) {
            $admin->givePermissionTo(Permission::findOrCreate("{$accion}:Expediente", 'web'));
        }
        $this->actingAs($admin);

        $contacto = Contacto::create(['nombre' => 'Ana Prueba', 'asesor_id' => $admin->id]);
        $this->expediente = Expediente::paraContacto($contacto, $admin);

        DocumentoExpediente::create([
            'expediente_id' => $this->expediente->id, 'tipo' => 'INE', 'nombre' => 'INE',
            'seccion' => 'acreditado', 'estado' => 'recibido', 'ruta_archivo' => 'expedientes/1/docs/ine.pdf',
        ]);
    }

    public function test_el_listado_de_expedientes_se_ve_y_filtra_por_capturar(): void
    {
        Livewire::test(ListExpedientes::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$this->expediente])
            ->filterTable('por_capturar')
            ->assertCanSeeTableRecords([$this->expediente]);
    }

    public function test_el_expediente_se_edita_sin_etapa_ni_estado(): void
    {
        Livewire::test(EditExpediente::class, ['record' => $this->expediente->getKey()])
            ->assertSuccessful()
            ->assertFormFieldDoesNotExist('etapa_tramite_id')
            ->assertFormFieldDoesNotExist('estado')
            ->fillForm(['acreditado_nombre' => 'Ana Capturada'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Ana Capturada', $this->expediente->fresh()->acreditado_nombre);
    }

    public function test_los_documentos_se_marcan_como_capturados(): void
    {
        $doc = $this->expediente->documentos()->first();

        Livewire::test(DocumentosRelationManager::class, [
            'ownerRecord' => $this->expediente,
            'pageClass'   => EditExpediente::class,
        ])
            ->assertCanSeeTableRecords([$doc])
            ->callTableAction('marcar_capturado', $doc);

        $this->assertNotNull($doc->fresh()->capturado_at);
    }
}
