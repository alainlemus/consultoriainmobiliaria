<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublicarPostsProgramados extends Command
{
    protected $signature   = 'posts:publicar-programados';
    protected $description = 'Publica automáticamente los artículos programados cuya fecha ya llegó.';

    public function handle(): int
    {
        $pendientes = Post::where('estado', Post::ESTADO_PROGRAMADO)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info('No hay artículos programados pendientes de publicación.');
            return self::SUCCESS;
        }

        foreach ($pendientes as $post) {
            $post->update(['estado' => Post::ESTADO_PUBLICADO]);
            $this->info("Publicado: [{$post->id}] {$post->titulo}");
        }

        $this->info("Total publicados: {$pendientes->count()}");
        return self::SUCCESS;
    }
}
