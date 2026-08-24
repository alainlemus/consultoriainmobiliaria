import Alpine from 'alpinejs';

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// x-reveal / x-reveal.delay.150 — fade+slide-up al entrar en viewport (una sola vez)
Alpine.directive('reveal', (el, { modifiers }, { cleanup }) => {
    if (prefersReducedMotion()) return;

    el.classList.add('reveal-init');

    const delayIndex = modifiers.indexOf('delay');
    if (delayIndex !== -1 && modifiers[delayIndex + 1]) {
        el.style.transitionDelay = `${modifiers[delayIndex + 1]}ms`;
    }

    // Si ya está en viewport al cargar (ej. above the fold), no animar
    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight && rect.bottom > 0) {
        el.classList.add('reveal-visible');
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                el.classList.add('reveal-visible');
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

    observer.observe(el);
    cleanup(() => observer.disconnect());
});

// countUp(target, { prefix, suffix, duration }) — cuenta de 0 al valor final al cargar
Alpine.data('countUp', (target, opts = {}) => ({
    display: '0',
    init() {
        const { prefix = '', suffix = '', duration = 1500 } = opts;

        if (prefersReducedMotion()) {
            this.display = `${prefix}${target}${suffix}`;
            return;
        }

        const start = performance.now();
        const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            this.display = `${prefix}${Math.round(target * eased)}${suffix}`;
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    },
}));

// carousel({ total, visibleDesktop, visibleTablet, visibleMobile, autoplayMs })
// Slider genérico con autoplay, prev/next, dots y pausa on-hover.
// Usado por el slider de fotos de clientes y el carrusel de testimonios (testimonios.blade.php).
Alpine.data('carousel', ({ total = 0, visibleDesktop = 4, visibleTablet = 2, visibleMobile = 1, autoplayMs = 3500 } = {}) => ({
    current: 0,
    total,
    visibles: 1,
    autoplay: null,
    init() {
        this.updateVisibles();
        window.addEventListener('resize', () => this.updateVisibles());
        this.startAutoplay();
    },
    updateVisibles() {
        this.visibles = window.innerWidth >= 1024 ? visibleDesktop : (window.innerWidth >= 640 ? visibleTablet : visibleMobile);
        if (this.current > this.maxIndex()) this.current = this.maxIndex();
    },
    maxIndex() {
        return Math.max(0, this.total - this.visibles);
    },
    prev() {
        this.current = this.current > 0 ? this.current - 1 : this.maxIndex();
        this.resetAutoplay();
    },
    next() {
        this.current = this.current < this.maxIndex() ? this.current + 1 : 0;
        this.resetAutoplay();
    },
    goTo(i) {
        this.current = i;
        this.resetAutoplay();
    },
    startAutoplay() {
        if (prefersReducedMotion()) return;
        this.autoplay = setInterval(() => this.next(), autoplayMs);
    },
    resetAutoplay() {
        clearInterval(this.autoplay);
        this.startAutoplay();
    },
    offset() {
        return `translateX(calc(-${this.current} * (100% / ${this.visibles})))`;
    },
}));

window.Alpine = Alpine;
Alpine.start();
