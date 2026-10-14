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
     class="fixed top-0 left-0 right-0 z-40 px-3 sm:px-6 pt-3 transition-all duration-700 pointer-events-none" style="view-transition-name: navbar;">

    @php
        $isHome        = request()->routeIs('home');
        $isPropiedades = request()->routeIs('propiedades.*');
        $isBlog        = request()->routeIs('blog.*');
        $navLink       = 'relative text-[13px] font-medium tracking-wide transition-colors duration-300';
        $active        = 'text-white';
        $inactive      = 'text-white/65 hover:text-white';
    @endphp

    {{-- Píldora de vidrio esmerilado: se compacta y oscurece al hacer scroll (estilo Apple) --}}
    <div class="pointer-events-auto mx-auto rounded-full transition-all duration-700 ease-[cubic-bezier(0.16,1,0.3,1)]"
         :class="scrolled
            ? 'max-w-5xl px-5 bg-dark-900/70 border border-white/10 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.6)] backdrop-blur-2xl backdrop-saturate-150'
            : 'max-w-7xl px-4 sm:px-6 lg:px-8 bg-transparent border border-transparent'">
        <div class="flex items-center justify-between transition-all duration-700" :class="scrolled ? 'h-14' : 'h-20'">

            <a href="{{ route('home') }}" class="flex items-center gap-3">
                @if(setting('logo'))
                    <img src="{{ Storage::url(setting('logo')) }}" alt="{{ setting('site_name', 'Consultoría Inmobiliaria') }}" class="w-auto object-contain transition-all duration-700" :class="scrolled ? 'h-8' : 'h-11'">
                @else
                    <svg viewBox="0 0 100 110" class="transition-all duration-700" :class="scrolled ? 'w-8 h-8' : 'w-11 h-11'" xmlns="http://www.w3.org/2000/svg">
                        <path d="M50 5 L95 25 L95 60 Q95 90 50 105 Q5 90 5 60 L5 25 Z" fill="#1a1a1a" stroke="#C9A84C" stroke-width="3"/>
                        <rect x="20" y="22" width="60" height="53" rx="2" fill="#2a2a2a"/>
                        <rect x="10" y="10" width="80" height="14" rx="2" fill="#8B1A1A"/>
                        <text x="50" y="21" text-anchor="middle" fill="#C9A84C" font-size="6" font-weight="bold" font-family="Arial">BIENES RAÍCES</text>
                    </svg>
                @endif
                <div class="leading-tight">
                    <span class="block text-gold-400 font-serif font-bold tracking-wide transition-all duration-700" :class="scrolled ? 'text-base' : 'text-lg'">CONSULTORÍA</span>
                    <span class="block text-white/70 text-[10px] uppercase tracking-[0.3em]">Inmobiliaria</span>
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

                <a href="{{ route('home') }}#contacto" class="btn-gold !py-2 !px-5 text-[13px]" x-magnetic>Contáctanos</a>
            </div>

            <button
                type="button"
                @click="open = !open"
                class="lg:hidden text-gold-400 p-2"
                :aria-label="open ? 'Cerrar menú' : 'Abrir menú'"
                aria-controls="menu-movil"
                :aria-expanded="open"
            >
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Backdrop del menú móvil: oscurece el resto de la página mientras está abierto --}}
    <div x-show="open" x-transition.opacity @click="open = false"
         class="lg:hidden fixed inset-0 top-0 bg-dark-900/70 backdrop-blur-sm pointer-events-auto"></div>

    {{-- Mobile --}}
    <div id="menu-movil" x-show="open" x-transition:enter="transition ease-[cubic-bezier(0.16,1,0.3,1)] duration-500" x-transition:enter-start="opacity-0 -translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition duration-200" x-transition:leave-end="opacity-0 -translate-y-2"
         class="lg:hidden relative z-10 mx-auto mt-2 max-w-md rounded-3xl bg-dark-900/90 backdrop-blur-2xl border border-white/10 pointer-events-auto overflow-hidden">
        <div class="px-6 py-4 space-y-1">
            <a href="{{ route('home') }}"
               class="block py-3 text-base font-medium border-b border-white/10 {{ $isHome ? 'text-gold-400' : 'text-cream-200 hover:text-gold-400' }}">Inicio</a>
            <a href="{{ route('home') }}#servicios"
               class="block py-3 text-base font-medium border-b border-white/10 text-cream-200 hover:text-gold-400">Servicios</a>
            <a href="{{ route('home') }}#proceso"
               class="block py-3 text-base font-medium border-b border-white/10 text-cream-200 hover:text-gold-400">Proceso</a>
            <a href="{{ route('home') }}#cobertura"
               class="block py-3 text-base font-medium border-b border-white/10 text-cream-200 hover:text-gold-400">Cobertura</a>
            <a href="{{ route('propiedades.index') }}"
               class="block py-3 text-base font-medium border-b border-white/10 {{ $isPropiedades ? 'text-gold-400' : 'text-cream-200 hover:text-gold-400' }}">Propiedades</a>
            <a href="{{ route('blog.index') }}"
               class="block py-3 text-base font-medium border-b border-white/10 {{ $isBlog ? 'text-gold-400' : 'text-cream-200 hover:text-gold-400' }}">Blog</a>
            <a href="{{ route('home') }}#contacto" class="btn-gold w-full justify-center mt-3">Contáctanos</a>
        </div>
    </div>
</nav>
