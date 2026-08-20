<nav x-data="{
        open: false,
        scrolled: false,
        activeSection: 'inicio',
        initObserver() {
            if (window.location.pathname !== '/') return;
            const sections = ['servicios','proceso','cobertura','propiedades','testimonios','blog','contacto'];
            const opts = { rootMargin: '-40% 0px -55% 0px', threshold: 0 };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) this.activeSection = e.target.id; });
            }, opts);
            sections.forEach(id => {
                const el = document.getElementById(id);
                if (el) observer.observe(el);
            });
        }
     }"
     x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 50 }); initObserver()"
     :class="scrolled ? 'bg-dark-900/95 backdrop-blur-md shadow-lg' : 'bg-transparent'"
     class="fixed top-0 left-0 right-0 z-40 transition-all duration-500" style="view-transition-name: navbar;">

    {{-- Barra rojo ladrillo superior — evoca construcción, igual que en el logo del manual --}}
    <div class="h-1 w-full bg-crimson-500"></div>

    @php
        $isHome        = request()->routeIs('home');
        $isPropiedades = request()->routeIs('propiedades.*');
        $isBlog        = request()->routeIs('blog.*');
        $navLink       = 'text-sm uppercase tracking-wider transition-colors duration-200';
        $active        = 'text-gold-400 border-b border-gold-400 pb-0.5';
        $inactive      = 'text-cream-200 hover:text-gold-400';
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">

            <a href="{{ route('home') }}" class="flex items-center gap-3">
                @if(setting('logo'))
                    <img src="{{ Storage::url(setting('logo')) }}" alt="{{ setting('site_name', 'Consultoría Inmobiliaria') }}" class="h-11 w-auto object-contain">
                @else
                    <svg viewBox="0 0 100 110" class="w-11 h-11" xmlns="http://www.w3.org/2000/svg">
                        <path d="M50 5 L95 25 L95 60 Q95 90 50 105 Q5 90 5 60 L5 25 Z" fill="#1a1a1a" stroke="#C9A84C" stroke-width="3"/>
                        <rect x="20" y="22" width="60" height="53" rx="2" fill="#2a2a2a"/>
                        <rect x="10" y="10" width="80" height="14" rx="2" fill="#8B1A1A"/>
                        <text x="50" y="21" text-anchor="middle" fill="#C9A84C" font-size="6" font-weight="bold" font-family="Arial">BIENES RAÍCES</text>
                    </svg>
                @endif
                <div class="hidden sm:block leading-tight">
                    <span class="block text-gold-400 font-serif font-bold text-lg tracking-wide">CONSULTORÍA</span>
                    <span class="block text-cream-200 text-xs uppercase tracking-[0.3em]">Inmobiliaria</span>
                </div>
            </a>

            {{-- Desktop --}}
            <div class="hidden lg:flex items-center gap-8">

                {{-- Inicio --}}
                <a href="{{ route('home') }}"
                   :class="'{{ $isHome }}' === '1' && activeSection === 'inicio' ? '{{ $active }}' : '{{ $inactive }}'"
                   class="{{ $navLink }}">Inicio</a>

                {{-- Servicios --}}
                <a href="{{ route('home') }}#servicios"
                   :class="'{{ $isHome }}' === '1' && activeSection === 'servicios' ? '{{ $active }}' : '{{ $inactive }}'"
                   class="{{ $navLink }}">Servicios</a>

                {{-- Proceso --}}
                <a href="{{ route('home') }}#proceso"
                   :class="'{{ $isHome }}' === '1' && activeSection === 'proceso' ? '{{ $active }}' : '{{ $inactive }}'"
                   class="{{ $navLink }}">Proceso</a>

                {{-- Cobertura --}}
                <a href="{{ route('home') }}#cobertura"
                   :class="'{{ $isHome }}' === '1' && activeSection === 'cobertura' ? '{{ $active }}' : '{{ $inactive }}'"
                   class="{{ $navLink }}">Cobertura</a>

                {{-- Propiedades --}}
                <a href="{{ route('propiedades.index') }}"
                   :class="('{{ $isPropiedades }}' === '1' || ('{{ $isHome }}' === '1' && activeSection === 'propiedades')) ? '{{ $active }}' : '{{ $inactive }}'"
                   class="{{ $navLink }}">Propiedades</a>

                {{-- Blog --}}
                <a href="{{ route('blog.index') }}"
                   :class="('{{ $isBlog }}' === '1' || ('{{ $isHome }}' === '1' && activeSection === 'blog')) ? '{{ $active }}' : '{{ $inactive }}'"
                   class="{{ $navLink }}">Blog</a>

                <a href="{{ route('home') }}#contacto" class="btn-gold text-xs">Contáctanos</a>
            </div>

            <button @click="open = !open" class="lg:hidden text-gold-400 p-2">
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Backdrop del menú móvil: oscurece el resto de la página mientras está abierto --}}
    <div x-show="open" x-transition.opacity @click="open = false"
         class="lg:hidden fixed inset-0 top-20 bg-dark-900/80"></div>

    {{-- Mobile --}}
    <div x-show="open" x-transition class="lg:hidden bg-dark-900 border-t border-gold-500/20">
        <div class="px-6 py-4 space-y-1">
            <a href="{{ route('home') }}"
               class="block py-2 text-sm uppercase tracking-wider border-b border-dark-600 {{ $isHome ? 'text-gold-400' : 'text-cream-200 hover:text-gold-400' }}">Inicio</a>
            <a href="{{ route('home') }}#servicios"
               class="block py-2 text-sm uppercase tracking-wider border-b border-dark-600 text-cream-200 hover:text-gold-400">Servicios</a>
            <a href="{{ route('home') }}#proceso"
               class="block py-2 text-sm uppercase tracking-wider border-b border-dark-600 text-cream-200 hover:text-gold-400">Proceso</a>
            <a href="{{ route('home') }}#cobertura"
               class="block py-2 text-sm uppercase tracking-wider border-b border-dark-600 text-cream-200 hover:text-gold-400">Cobertura</a>
            <a href="{{ route('propiedades.index') }}"
               class="block py-2 text-sm uppercase tracking-wider border-b border-dark-600 {{ $isPropiedades ? 'text-gold-400' : 'text-cream-200 hover:text-gold-400' }}">Propiedades</a>
            <a href="{{ route('blog.index') }}"
               class="block py-2 text-sm uppercase tracking-wider border-b border-dark-600 {{ $isBlog ? 'text-gold-400' : 'text-cream-200 hover:text-gold-400' }}">Blog</a>
            <a href="{{ route('home') }}#contacto" class="btn-gold w-full justify-center mt-3">Contáctanos</a>
        </div>
    </div>
</nav>
