<?php

namespace Tests\Feature;

use App\Models\Propiedad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MenuPropiedadesTest extends TestCase
{
    use RefreshDatabase;

    private function propiedad(string $estatus): Propiedad
    {
        return Propiedad::create([
            'titulo' => 'Casa ' . uniqid(), 'tipo' => 'casa', 'precio' => 1000000,
            'estado' => 'Hidalgo', 'municipio' => 'Huejutla', 'estatus' => $estatus,
        ]);
    }

    private function enlaceEnMenu(): bool
    {
        return str_contains($this->get('/')->getContent(), 'href="' . route('propiedades.index') . '"');
    }

    public function test_sin_propiedades_en_venta_el_menu_no_muestra_propiedades(): void
    {
        $this->assertFalse($this->enlaceEnMenu());

        // Una vendida u oculta tampoco cuenta
        $this->propiedad('vendida');
        $this->assertFalse($this->enlaceEnMenu());
    }

    public function test_con_una_propiedad_en_venta_el_menu_la_muestra_y_se_actualiza_al_cambiar(): void
    {
        $p = $this->propiedad('en_venta');
        $this->assertTrue($this->enlaceEnMenu());

        // Al venderse desaparece (se invalida el caché al guardar)
        $p->update(['estatus' => 'vendida']);
        $this->assertFalse($this->enlaceEnMenu());

        // Al borrarse la última también
        $q = $this->propiedad('en_venta');
        $this->assertTrue($this->enlaceEnMenu());
        $q->delete();
        $this->assertFalse($this->enlaceEnMenu());
    }

    public function test_el_sitemap_solo_lista_propiedades_si_hay(): void
    {
        $this->assertStringNotContainsString(route('propiedades.index'), $this->get('/sitemap.xml')->getContent());
        $this->propiedad('en_venta');
        $this->assertStringContainsString(route('propiedades.index'), $this->get('/sitemap.xml')->getContent());
    }
}
