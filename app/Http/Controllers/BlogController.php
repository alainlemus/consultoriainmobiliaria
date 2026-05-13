<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::published()
            ->when($request->categoria, fn($q, $c) => $q->where('categoria', $c))
            ->latest('published_at')
            ->paginate(9);

        $categorias = Post::published()->distinct()->pluck('categoria')->filter()->sort()->values();

        return view('pages.blog.index', compact('posts', 'categorias'));
    }

    public function show(string $slug)
    {
        $post = Post::published()->where('slug', $slug)->firstOrFail();
        $relacionados = Post::published()
            ->where('id', '!=', $post->id)
            ->where('categoria', $post->categoria)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('pages.blog.show', compact('post', 'relacionados'));
    }
}
