<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

    {{-- Home --}}
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Propiedades index --}}
    <url>
        <loc>{{ route('propiedades.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    {{-- Blog index --}}
    <url>
        <loc>{{ route('blog.index') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    {{-- Descarga la app --}}
    <url>
        <loc>{{ route('descargar.app') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    {{-- Aviso de privacidad --}}
    <url>
        <loc>{{ route('aviso.privacidad') }}</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>

    {{-- Propiedades individuales --}}
    @foreach($propiedades as $propiedad)
    <url>
        <loc>{{ route('propiedades.show', $propiedad->slug) }}</loc>
        <lastmod>{{ $propiedad->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach

    {{-- Posts del blog --}}
    @foreach($posts as $post)
    <url>
        <loc>{{ route('blog.show', $post->slug) }}</loc>
        <lastmod>{{ $post->updated_at->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    @endforeach

</urlset>
