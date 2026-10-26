<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContratosAsesorTest extends TestCase
{
    use RefreshDatabase;

    private function contrato(string $localId): array
    {
        return [
            'local_id'          => $localId,
            'acreditado_nombre' => 'Ana Prueba',
            'solidario_nombre'  => 'Luis Solidario',
            'pdf'               => UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf'),
        ];
    }

    public function test_cualquier_asesor_puede_crear_contratos_y_solo_ve_los_suyos(): void
    {
        Storage::fake('local');
        Role::findOrCreate('asesor', 'web');
        Role::findOrCreate('super_admin', 'web');

        $uno  = User::factory()->create();
        $dos  = User::factory()->create();
        $admin = User::factory()->create();
        $uno->assignRole('asesor');
        $dos->assignRole('asesor');
        $admin->assignRole('super_admin');

        Sanctum::actingAs($uno);
        $this->postJson('/api/v1/contratos/generados', $this->contrato('a-1'))->assertSuccessful();

        Sanctum::actingAs($dos);
        $this->postJson('/api/v1/contratos/generados', $this->contrato('b-1'))->assertSuccessful();
        $this->getJson('/api/v1/contratos/generados')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/contratos/generados')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_el_asesor_puede_ver_el_pagare_de_su_contrato(): void
    {
        Storage::fake('local');
        Role::findOrCreate('asesor', 'web');

        $asesor = User::factory()->create();
        $otro   = User::factory()->create();
        $asesor->assignRole('asesor');
        $otro->assignRole('asesor');

        Sanctum::actingAs($asesor);
        $id = $this->postJson('/api/v1/contratos/generados', [
            ...$this->contrato('p-1'),
            'acreditado_rfc'       => 'PRUE800101AAA',
            'acreditado_domicilio' => 'Calle Uno 1, Pachuca',
        ])->assertSuccessful()->json('data.id');

        $url = $this->getJson("/api/v1/contratos/generados/{$id}/ver")
            ->assertOk()
            ->json('pagare_url');
        $this->assertNotEmpty($url);

        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('api.contratos_generados.pagare', ['id' => $id]))->assertForbidden();

        Sanctum::actingAs($otro);
        $this->getJson("/api/v1/contratos/generados/{$id}/ver")->assertForbidden();
    }

    public function test_solo_el_administrador_edita_la_plantilla(): void
    {
        Role::findOrCreate('asesor', 'web');
        $asesor = User::factory()->create();
        $asesor->assignRole('asesor');

        Sanctum::actingAs($asesor);
        $this->putJson('/api/v1/contratos/prestacion-servicios/config', ['texto' => 'x'])->assertForbidden();
    }
}
