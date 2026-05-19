<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Propiedad;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $posts = Post::where('publicado', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at']);

        $propiedades = Propiedad::whereIn('estatus', ['en_venta', 'disponible'])
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        $content = view('sitemap', compact('posts', 'propiedades'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
