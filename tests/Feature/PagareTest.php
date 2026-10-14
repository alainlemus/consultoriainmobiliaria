<?php

namespace Tests\Feature;

use App\Models\Contacto;
use App\Models\ContratoGenerado;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Services\PagareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class PagareTest extends TestCase
{
    use RefreshDatabase;

    private function documentoXml(string $ruta): string
    {
        $zip = new ZipArchive();
        $zip->open($ruta);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }

    public function test_el_word_se_llena_con_los_datos_del_acreditado_y_conserva_el_formato(): void
    {
        $servicio = new PagareService();
        $datos = $servicio->datos('Gilberto Hernández Hernández', 'Calle 1, Col. Centro, Huejutla', 'HEHG800101ABC', Carbon::create(2026, 10, 14));

        $ruta = $servicio->docx($datos);
        $xml  = $this->documentoXml($ruta);
        unlink($ruta);

        $this->assertStringContainsString('A 14 DE OCTUBRE DE 2026.', $xml);
        $this->assertStringContainsString('YO, C. GILBERTO HERNÁNDEZ HERNÁNDEZ, CON DOMICILIO EN CALLE 1, COL. CENTRO, HUEJUTLA, Y RFC: HEHG800101ABC, EN ADELANTE', $xml);
        // El texto fijo del formato sigue intacto (acreedor, intereses, jurisdicción)
        $this->assertStringContainsString('SOSA860530LM7', $xml);
        $this->assertStringContainsString('3% ANUAL', $xml);
        $this->assertStringContainsString('NOMBRE Y FIRMA DE EL DEUDOR', $xml);
    }

    public function test_sin_datos_se_dejan_las_rayas_para_llenar_a_mano(): void
    {
        $servicio = new PagareService();
        $ruta = $servicio->docx($servicio->datos(null, null, null, Carbon::create(2026, 1, 5)));
        $xml  = $this->documentoXml($ruta);
        unlink($ruta);

        $this->assertMatchesRegularExpression('/YO, C\. _+, CON DOMICILIO EN _+, Y RFC: _+,/', $xml);
        $this->assertStringContainsString('A 5 DE ENERO DE 2026.', $xml);
    }

    public function test_los_caracteres_especiales_se_escapan_en_el_word(): void
    {
        $servicio = new PagareService();
        $ruta = $servicio->docx($servicio->datos('Pérez & Hijos <SA>', 'Calle "A"', 'X', Carbon::create(2026, 3, 1)));
        $xml  = $this->documentoXml($ruta);
        unlink($ruta);

        $this->assertStringContainsString('PÉREZ &amp; HIJOS &lt;SA&gt;', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'El XML del Word debe seguir siendo válido');
    }

    public function test_el_pagare_se_descarga_en_pdf_y_word_desde_el_contrato_generado(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('asesor', 'web');
        $admin  = User::factory()->create();
        $admin->assignRole('super_admin');
        $asesor = User::factory()->create();
        $asesor->assignRole('asesor');

        $contrato = ContratoGenerado::create([
            'local_id' => 'x-1', 'asesor_id' => $asesor->id, 'acreditado_nombre' => 'Ana Prueba',
            'acreditado_rfc' => 'ABCD800101XYZ', 'acreditado_domicilio' => 'Calle 5', 'solidario_nombre' => 'Luis', 'pdf_path' => '',
        ]);

        $this->actingAs($admin)->get(route('pagare.contrato_generado', $contrato))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)->get(route('pagare.contrato_generado', $contrato) . '?formato=word')
            ->assertOk()->assertHeader('content-disposition');

        // Solo el administrador usa el del contrato generado
        $this->actingAs($asesor)->get(route('pagare.contrato_generado', $contrato))->assertForbidden();
    }

    public function test_el_pagare_del_expediente_respeta_al_asesor_duenio(): void
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('asesor', 'web');
        $duenio = User::factory()->create();
        $duenio->assignRole('asesor');
        $otro = User::factory()->create();
        $otro->assignRole('asesor');

        $tipo  = TipoTramite::create(['nombre' => 'FOVISSSTE', 'slug' => 'fovissste']);
        $etapa = EtapaTramite::create(['tipo_tramite_id' => $tipo->id, 'nombre' => 'Inicio', 'orden' => 1]);
        $contacto = Contacto::create(['nombre' => 'Ana', 'asesor_id' => $duenio->id]);
        $exp = Expediente::create([
            'contacto_id' => $contacto->id, 'asesor_id' => $duenio->id, 'tipo_tramite_id' => $tipo->id,
            'etapa_tramite_id' => $etapa->id, 'acreditado_nombre' => 'Ana Prueba', 'acreditado_rfc' => 'ABCD800101XYZ',
        ]);

        $this->actingAs($duenio)->get(route('pagare.expediente', $exp))->assertOk();
        $this->actingAs($otro)->get(route('pagare.expediente', $exp))->assertForbidden();
    }
}
