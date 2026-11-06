<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginasSeoTest extends TestCase
{
    use RefreshDatabase;

    /** Todas las páginas de servicio y de zona responden y traen su H1, título y canonical */
    public function test_paginas_de_servicio_y_zona_responden_con_seo_completo(): void
    {
        $rutas = [
            'servicios.avaluos'       => 'Avalúos inmobiliarios en Hidalgo',
            'servicios.infonavit'     => 'crédito INFONAVIT en Hidalgo',
            'servicios.escrituracion' => 'Escrituración y gestoría inmobiliaria',
            'zonas.huejutla'          => 'Consultoría inmobiliaria en Huejutla de Reyes',
            'zonas.pachuca'           => 'Consultoría inmobiliaria en Pachuca',
            'zonas.veracruz'          => 'Consultoría inmobiliaria en la Huasteca veracruzana',
            'zonas.slp'               => 'Consultoría inmobiliaria en San Luis Potosí',
        ];

        foreach ($rutas as $ruta => $h1) {
            $html = $this->get(route($ruta))->assertOk()->getContent();

            $this->assertStringContainsString($h1, $html, "H1 de {$ruta}");
            $this->assertStringContainsString('<link rel="canonical" href="' . route($ruta) . '">', $html, "canonical de {$ruta}");
            $this->assertMatchesRegularExpression('/<title>[^<]{20,}<\/title>/', $html, "title de {$ruta}");
            $this->assertJsonLdValido($html, $ruta);
        }
    }

    public function test_la_home_lleva_la_palabra_clave_en_titulo_h1_y_canonical_con_diagonal(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Consultoría Inmobiliaria en Hidalgo | Crédito INFONAVIT, FOVISSSTE y Avalúos</title>', $html);
        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*Consultoría Inmobiliaria en Huejutla de Reyes, Hidalgo\s*<\/h1>/', $html);
        $this->assertStringContainsString('<link rel="canonical" href="' . rtrim(config('app.url'), '/') . '/">', $html);
        $this->assertJsonLdValido($html, 'home');
    }

    public function test_propiedades_no_compite_con_la_home_por_la_marca(): void
    {
        $html = $this->get(route('propiedades.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Casas y terrenos en venta en Hidalgo, Veracruz y San Luis Potosí</title>', $html);
    }

    public function test_propiedades_no_se_indexa_mientras_esta_vacia(): void
    {
        $this->get(route('propiedades.index'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        \App\Models\Propiedad::create([
            'titulo' => 'Casa en Huejutla', 'slug' => 'casa-en-huejutla', 'tipo' => 'casa', 'precio' => 900000,
            'estado' => 'Hidalgo', 'municipio' => 'Huejutla', 'estatus' => 'en_venta',
        ]);
        \Illuminate\Support\Facades\Cache::forget(\App\Models\Propiedad::CACHE_HAY_EN_VENTA);

        $this->get(route('propiedades.index'))->assertOk()
            ->assertDontSee('noindex', false);
    }

    public function test_el_sitemap_incluye_las_paginas_nuevas(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['servicios.avaluos', 'servicios.infonavit', 'servicios.escrituracion', 'zonas.huejutla', 'zonas.pachuca', 'zonas.veracruz', 'zonas.slp'] as $ruta) {
            $this->assertStringContainsString('<loc>' . route($ruta) . '</loc>', $xml);
        }
        $this->assertStringContainsString('<loc>' . rtrim(config('app.url'), '/') . '/</loc>', $xml);
    }

    public function test_www_redirige_301_al_dominio_sin_www(): void
    {
        config(['app.url' => 'https://consultoriainmobiliaria.com.mx']);

        $this->get('https://www.consultoriainmobiliaria.com.mx/blog?x=1')
            ->assertStatus(301)
            ->assertRedirect('https://consultoriainmobiliaria.com.mx/blog?x=1');

        $this->get('https://consultoriainmobiliaria.com.mx/')->assertOk();
    }

    public function test_el_articulo_del_blog_enlaza_al_servicio_de_su_categoria(): void
    {
        $post = Post::create([
            'titulo' => 'Avalúo fiscal vs comercial', 'categoria' => 'Avalúos',
            'resumen' => 'Resumen', 'contenido' => '<p>Contenido</p>',
            'estado' => Post::ESTADO_PUBLICADO, 'published_at' => now()->subDay(),
        ]);

        $html = $this->get(route('blog.show', $post->slug))->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('servicios.avaluos') . '"', $html);
        $this->assertStringContainsString('<title>Avalúo fiscal vs comercial</title>', $html);
    }

    /** Cada bloque ld+json de la página debe ser JSON válido (p. ej. "position": 01 lo rompía) */
    private function assertJsonLdValido(string $html, string $pagina): void
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m[1], "sin JSON-LD en {$pagina}");

        foreach ($m[1] as $i => $bloque) {
            json_decode($bloque);
            $this->assertSame(JSON_ERROR_NONE, json_last_error(), "JSON-LD #{$i} inválido en {$pagina}: " . json_last_error_msg());
        }
    }
}
