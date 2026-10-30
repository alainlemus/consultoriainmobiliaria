<?php

namespace Tests\Feature;

use App\Models\ContratoGenerado;
use App\Models\User;
use App\Services\RegresionFasesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegresionFasesTest extends TestCase
{
    use RefreshDatabase;

    private function contrato(User $asesor, array $extra = []): ContratoGenerado
    {
        return ContratoGenerado::create([
            'local_id' => 'x-1', 'asesor_id' => $asesor->id, 'acreditado_nombre' => 'Juan Pérez Pérez',
            'solidario_nombre' => 'Juana Pérez Pérez', 'ciudad' => 'Huejutla de Reyes', 'pdf_path' => '', ...$extra,
        ]);
    }

    public function test_el_oficio_lleva_a_los_firmantes_y_el_lugar_y_fecha_del_contrato(): void
    {
        $contrato = $this->contrato(User::factory()->create());
        $contrato->created_at = Carbon::create(2026, 10, 29);

        $d = app(RegresionFasesService::class)->datos($contrato);

        $this->assertSame('Huejutla de Reyes, Estado de Hidalgo', $d['lugar']);
        $this->assertSame('29 de octubre de 2026', $d['fecha']);
        $this->assertSame('JUAN PÉREZ PÉREZ', $d['acreditado']);
        $this->assertSame('JUANA PÉREZ PÉREZ', $d['solidario']);

        $contrato->ciudad = 'Pachuca de Soto, Hidalgo';
        $this->assertSame('Pachuca de Soto, Hidalgo', app(RegresionFasesService::class)->datos($contrato)['lugar']);
    }

    public function test_el_oficio_se_ve_desde_el_panel_y_desde_la_app(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('asesor', 'web');
        $admin  = User::factory()->create();
        $admin->assignRole('super_admin');
        $asesor = User::factory()->create();
        $asesor->assignRole('asesor');
        $contrato = $this->contrato($asesor);

        $this->actingAs($admin)->get(route('pagare.regresion_fases', $contrato) . '?preview=1')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($asesor)->get(route('pagare.regresion_fases', $contrato))->assertForbidden();

        // La app recibe la URL firmada junto con la del pagaré
        $urls = $this->actingAs($asesor, 'sanctum')->getJson("/api/v1/contratos/generados/{$contrato->id}/ver")->assertOk()->json();
        $this->assertArrayHasKey('pagare_url', $urls);
        $this->get($urls['regresion_fases_url'])->assertOk()->assertHeader('content-type', 'application/pdf');
    }
}
