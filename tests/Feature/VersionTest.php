<?php

namespace Tests\Feature;

use App\Support\AppVersion;
use Tests\TestCase;

class VersionTest extends TestCase
{
    public function test_version_json_expone_la_version_del_archivo_version(): void
    {
        $this->getJson('/version.json')
            ->assertOk()
            ->assertJsonPath('version', trim(file_get_contents(base_path('VERSION'))))
            ->assertJsonStructure(['version', 'label', 'environment', 'commit', 'branch', 'built_at']);
    }

    public function test_fuera_de_produccion_la_etiqueta_lleva_dev(): void
    {
        $this->assertStringStartsWith('v'.AppVersion::number().'-dev', AppVersion::label());
    }
}
