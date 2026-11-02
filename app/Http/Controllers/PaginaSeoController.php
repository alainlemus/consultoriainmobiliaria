<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\PaginasSeo;
use Illuminate\Contracts\View\View;

/**
 * Páginas de servicio (/servicios/{slug}) y de zona (/consultoria-inmobiliaria/{slug}).
 * El contenido vive en App\Support\PaginasSeo.
 */
class PaginaSeoController extends Controller
{
    public function servicio(string $slug): View
    {
        $pagina = PaginasSeo::servicio($slug) ?? abort(404);

        return $this->render($slug, $pagina);
    }

    public function zona(string $slug): View
    {
        $pagina = PaginasSeo::zona($slug) ?? abort(404);

        return $this->render($slug, $pagina);
    }

    private function render(string $slug, array $pagina): View
    {
        $posts = Post::published()
            ->when($pagina['categoria_blog'] ?? null, fn ($q, $c) => $q->where('categoria', $c))
            ->latest('published_at')
            ->take(3)
            ->get();

        // Enlaces cruzados: todas las demás páginas de servicio y de zona
        $relacionadas = collect(PaginasSeo::servicios())
            ->merge(PaginasSeo::zonas())
            ->except($slug)
            ->map(fn ($p) => ['url' => route($p['ruta']), 'texto' => $p['h1']])
            ->values();

        return view('pages.seo.landing', compact('pagina', 'posts', 'relacionadas'));
    }
}
