/**
 * Capa de movimiento del sitio público (estilo Apple): revelados suaves, texto por palabras,
 * parallax y progreso ligados al scroll, tilt 3D con brillo, botones magnéticos y barra de progreso.
 *
 * Todo se activa solo si hay JS y se apaga con prefers-reduced-motion; sin JS el contenido se ve igual.
 * Uso desde Blade (directivas de Alpine):
 *   x-reveal / x-reveal.scale / x-reveal.blur / x-reveal.left / x-reveal.delay.150
 *   x-split                  → el texto entra palabra por palabra
 *   x-parallax.0.2           → se desplaza a 0.2× el scroll
 *   x-scrollprogress         → expone --p (0→1) mientras la sección cruza la pantalla
 *   x-tilt                   → inclinación 3D + luz que sigue al cursor
 *   x-magnetic               → el elemento se acerca ligeramente al cursor
 */
const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const canHover = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const clamp = (n, a = 0, b = 1) => Math.min(b, Math.max(a, n));

// ── Un solo bucle rAF para todo lo ligado al scroll ────────────────────────────
const scrollTasks = new Set();
let ticking = false;
function runScrollTasks() {
    ticking = false;
    scrollTasks.forEach((fn) => fn());
}
function requestScrollTick() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(runScrollTasks);
}
window.addEventListener('scroll', requestScrollTick, { passive: true });
window.addEventListener('resize', requestScrollTick, { passive: true });

// ── x-reveal ───────────────────────────────────────────────────────────────────
function reveal(el, { modifiers }, { cleanup }) {
    if (reduced()) return;

    el.classList.add('reveal-init');
    const variant = ['scale', 'blur', 'left', 'right'].find((v) => modifiers.includes(v));
    if (variant) el.dataset.reveal = variant;

    const i = modifiers.indexOf('delay');
    if (i !== -1 && modifiers[i + 1]) el.style.transitionDelay = `${modifiers[i + 1]}ms`;

    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight * 0.92 && rect.bottom > 0) {
        // Ya visible al cargar: entra con un pequeño retraso para que se note el movimiento
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('reveal-visible')));
        return;
    }

    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
            if (e.isIntersecting) {
                el.classList.add('reveal-visible');
                io.unobserve(el);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    io.observe(el);
    cleanup(() => io.disconnect());
}

// ── x-split: palabra por palabra con máscara ───────────────────────────────────
function split(el, { modifiers }, { cleanup }) {
    if (reduced()) return;

    const delayIdx = modifiers.indexOf('delay');
    const base = delayIdx !== -1 ? Number(modifiers[delayIdx + 1]) || 0 : 0;

    // Recorre los nodos de texto conservando etiquetas internas (<span class="text-gold-400">…)
    let n = 0;
    const walk = (node) => {
        [...node.childNodes].forEach((child) => {
            if (child.nodeType === 3) {
                const frag = document.createDocumentFragment();
                child.textContent.split(/(\s+)/).forEach((part) => {
                    if (!part) return;
                    if (/^\s+$/.test(part)) {
                        frag.appendChild(document.createTextNode(part));
                        return;
                    }
                    const mask = document.createElement('span');
                    mask.className = 'split-mask';
                    const word = document.createElement('span');
                    word.className = 'split-word';
                    word.style.transitionDelay = `${base + n * 70}ms`;
                    word.textContent = part;
                    mask.appendChild(word);
                    frag.appendChild(mask);
                    n++;
                });
                child.replaceWith(frag);
            } else if (child.nodeType === 1 && child.tagName !== 'BR') {
                walk(child);
            }
        });
    };
    walk(el);
    el.classList.add('split-init');

    const show = () => el.classList.add('split-visible');
    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight * 0.95) {
        requestAnimationFrame(() => requestAnimationFrame(show));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) {
            show();
            io.disconnect();
        }
    }, { threshold: 0.2 });
    io.observe(el);
    cleanup(() => io.disconnect());
}

// ── x-parallax.<velocidad> ─────────────────────────────────────────────────────
function parallax(el, { modifiers }, { cleanup }) {
    if (reduced()) return;
    // Alpine separa "0.2" en ["0", "2"] por el punto; se vuelve a unir
    const speed = parseFloat(modifiers.join('.')) || 0.15;
    const task = () => {
        const r = el.parentElement.getBoundingClientRect();
        if (r.bottom < -200 || r.top > window.innerHeight + 200) return;
        const offset = (r.top + r.height / 2 - window.innerHeight / 2) * speed;
        el.style.transform = `translate3d(0, ${offset.toFixed(1)}px, 0)`;
    };
    scrollTasks.add(task);
    task();
    cleanup(() => scrollTasks.delete(task));
}

// ── x-scrollprogress: --p de 0 a 1 mientras la sección cruza la pantalla ───────
function scrollprogress(el, { modifiers }, { cleanup }) {
    if (reduced()) {
        // Sin movimiento: la sección "fija" se queda en su estado inicial; las demás, en su estado final
        el.style.setProperty('--p', modifiers.includes('sticky') ? '0' : '1');
        return;
    }
    // .sticky → progreso a lo largo del tramo en que la sección está fija (altura - viewport)
    const sticky = modifiers.includes('sticky');
    const task = () => {
        const r = el.getBoundingClientRect();
        const vh = window.innerHeight;
        const p = sticky
            ? clamp(-r.top / Math.max(1, r.height - vh))
            : clamp((vh - r.top) / (vh + r.height));
        el.style.setProperty('--p', p.toFixed(4));
    };
    scrollTasks.add(task);
    task();
    cleanup(() => scrollTasks.delete(task));
}

// ── x-tilt: inclinación 3D + brillo que sigue al cursor ────────────────────────
function tilt(el, { modifiers }, { cleanup }) {
    if (reduced() || !canHover()) return;
    const max = Number(modifiers.find((m) => /^\d+$/.test(m))) || 7;
    el.classList.add('tilt');
    let raf = 0;

    const move = (e) => {
        const r = el.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width;
        const y = (e.clientY - r.top) / r.height;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => {
            el.style.setProperty('--rx', `${((0.5 - y) * max).toFixed(2)}deg`);
            el.style.setProperty('--ry', `${((x - 0.5) * max).toFixed(2)}deg`);
            el.style.setProperty('--mx', `${(x * 100).toFixed(1)}%`);
            el.style.setProperty('--my', `${(y * 100).toFixed(1)}%`);
        });
    };
    const leave = () => {
        cancelAnimationFrame(raf);
        el.style.setProperty('--rx', '0deg');
        el.style.setProperty('--ry', '0deg');
    };
    el.addEventListener('pointermove', move);
    el.addEventListener('pointerleave', leave);
    cleanup(() => {
        el.removeEventListener('pointermove', move);
        el.removeEventListener('pointerleave', leave);
    });
}

// ── x-magnetic ─────────────────────────────────────────────────────────────────
function magnetic(el, { modifiers }, { cleanup }) {
    if (reduced() || !canHover()) return;
    const strength = Number(modifiers.find((m) => /^\d+$/.test(m))) || 0.25;
    el.classList.add('magnetic');

    const move = (e) => {
        const r = el.getBoundingClientRect();
        const dx = e.clientX - (r.left + r.width / 2);
        const dy = e.clientY - (r.top + r.height / 2);
        el.style.transform = `translate3d(${(dx * strength).toFixed(1)}px, ${(dy * strength).toFixed(1)}px, 0)`;
    };
    const leave = () => { el.style.transform = ''; };
    el.addEventListener('pointermove', move);
    el.addEventListener('pointerleave', leave);
    cleanup(() => {
        el.removeEventListener('pointermove', move);
        el.removeEventListener('pointerleave', leave);
    });
}

// ── Barra de progreso de lectura + estado del scroll en <html> ─────────────────
function initGlobal() {
    const bar = document.createElement('div');
    bar.className = 'scroll-progress';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);

    const root = document.documentElement;
    const task = () => {
        const max = root.scrollHeight - window.innerHeight;
        bar.style.transform = `scaleX(${max > 0 ? clamp(window.scrollY / max) : 0})`;
        root.classList.toggle('is-scrolled', window.scrollY > 24);
    };
    scrollTasks.add(task);
    task();

    // Luz que sigue al cursor en cualquier elemento con [data-spotlight]
    if (!reduced() && canHover()) {
        document.addEventListener('pointermove', (e) => {
            const el = e.target.closest?.('[data-spotlight]');
            if (!el) return;
            const r = el.getBoundingClientRect();
            el.style.setProperty('--sx', `${e.clientX - r.left}px`);
            el.style.setProperty('--sy', `${e.clientY - r.top}px`);
        }, { passive: true });
    }
}

export function registerMotion(Alpine) {
    Alpine.directive('reveal', reveal);
    Alpine.directive('split', split);
    Alpine.directive('parallax', parallax);
    Alpine.directive('scrollprogress', scrollprogress);
    Alpine.directive('tilt', tilt);
    Alpine.directive('magnetic', magnetic);
    document.addEventListener('DOMContentLoaded', initGlobal);
}
