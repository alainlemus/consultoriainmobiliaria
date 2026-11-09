<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Archivos /.well-known que permiten que la liga del QR abra la app instalada. */
class EnlacesAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_apple_app_site_association_lleva_team_id_y_la_ruta_del_qr(): void
    {
        Configuracion::set('apple_team_id', '7CKS7PY2UG');

        $this->get('/.well-known/apple-app-site-association')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('applinks.details.0.appIDs.0', '7CKS7PY2UG.com.consultoriainmobiliaria.app')
            ->assertJsonPath('applinks.details.0.components.0./', '/a/*');
    }

    public function test_sin_team_id_el_archivo_sigue_siendo_valido_pero_vacio(): void
    {
        Configuracion::set('apple_team_id', '');
        $this->get('/.well-known/apple-app-site-association')->assertOk()->assertJsonPath('applinks.details', []);
    }

    public function test_assetlinks_lleva_el_paquete_y_las_huellas_normalizadas(): void
    {
        $huella = implode(':', array_fill(0, 32, 'ab'));
        Configuracion::set('android_sha256', " {$huella} \n basura-no-valida ");

        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('0.target.package_name', 'com.consultoriainmobiliaria.app')
            ->assertJsonPath('0.target.sha256_cert_fingerprints', [strtoupper($huella)])
            ->assertJsonPath('0.relation.0', 'delegate_permission/common.handle_all_urls');
    }

    public function test_assetlinks_sin_huella_es_una_lista_vacia(): void
    {
        Configuracion::set('android_sha256', '');
        $this->get('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);
    }

    public function test_sin_configurar_nada_salen_el_team_id_y_la_huella_de_play_por_defecto(): void
    {
        $this->get('/.well-known/apple-app-site-association')
            ->assertJsonPath('applinks.details.0.appIDs.0', '7CKS7PY2UG.com.consultoriainmobiliaria.app');

        $this->get('/.well-known/assetlinks.json')
            ->assertJsonPath('0.target.sha256_cert_fingerprints.0', '5A:2E:08:72:01:22:79:0F:CB:30:37:21:20:07:FF:EF:FB:E8:40:48:49:FE:06:99:0B:17:D2:E8:A4:1D:7E:0A');
    }
}
