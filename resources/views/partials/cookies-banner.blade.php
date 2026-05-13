{{-- Banner de consentimiento de cookies / privacidad --}}
<div
    x-data="cookieConsent()"
    x-show="visible"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-8"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-8"
    x-cloak
    class="fixed bottom-0 left-0 right-0 z-[60] bg-dark-900/98 backdrop-blur-md border-t border-gold-500/30 shadow-2xl"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">

            {{-- Ícono --}}
            <div class="shrink-0 w-10 h-10 bg-gold-500/10 border border-gold-500/30 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-gold-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>

            {{-- Texto --}}
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-semibold mb-1">Privacidad y uso de cookies</p>
                <p class="text-cream-300/80 text-xs leading-relaxed">
                    Utilizamos cookies propias para mejorar tu experiencia de navegación y analizar el tráfico del sitio.
                    Al continuar navegando aceptas nuestra
                    <a href="{{ route('aviso.privacidad') }}" target="_blank"
                       class="text-gold-400 hover:text-gold-300 underline underline-offset-2 transition-colors">
                        política de privacidad
                    </a>
                    y el uso de cookies.
                </p>
            </div>

            {{-- Botones --}}
            <div class="flex items-center gap-3 shrink-0">
                <button @click="reject()"
                        class="text-cream-300/60 hover:text-cream-300 text-xs underline underline-offset-2 transition-colors whitespace-nowrap">
                    Solo esenciales
                </button>
                <button @click="accept()"
                        class="btn-gold text-xs px-5 py-2 whitespace-nowrap">
                    Aceptar todo
                </button>
            </div>

        </div>
    </div>
</div>

<script>
function cookieConsent() {
    const KEY     = 'cookie_consent';
    const EXPIRES = 30; // días

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/;SameSite=Lax`;
    }

    return {
        visible: false,

        init() {
            // Mostrar solo si no ha dado consentimiento aún
            if (!getCookie(KEY) && !localStorage.getItem(KEY)) {
                // Pequeño delay para no aparecer de golpe
                setTimeout(() => { this.visible = true; }, 800);
            }
        },

        accept() {
            setCookie(KEY, 'accepted', EXPIRES);
            localStorage.setItem(KEY, 'accepted');
            this.visible = false;
        },

        reject() {
            setCookie(KEY, 'essential', EXPIRES);
            localStorage.setItem(KEY, 'essential');
            this.visible = false;
        },
    }
}
</script>
