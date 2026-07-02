{{-- ── Carousel de fotos de anuncios (modal JS puro, z-index sobre Filament) ── --}}
{{-- Se inyecta solo en ListAnuncios via AppServiceProvider::renderHook        --}}
<script>
(function () {
    if (window.__carouselAnuncioInited) return;
    window.__carouselAnuncioInited = true;

    let urls = [];
    let idx  = 0;

    // ── Overlay ──────────────────────────────────────────────────────────────
    const overlay = document.createElement('div');
    overlay.style.cssText = [
        'display:none', 'position:fixed', 'inset:0', 'z-index:99999',
        'background:rgba(0,0,0,0.88)', 'align-items:center',
        'justify-content:center', 'padding:16px', 'cursor:zoom-out',
    ].join(';');

    // ── Contenedor central ───────────────────────────────────────────────────
    const inner = document.createElement('div');
    inner.style.cssText = 'position:relative;max-width:900px;width:100%;cursor:default;display:flex;flex-direction:column;align-items:center;gap:12px;';
    inner.addEventListener('click', e => e.stopPropagation());

    // ── Barra superior: contador + cerrar ────────────────────────────────────
    const topBar = document.createElement('div');
    topBar.style.cssText = 'width:100%;display:flex;align-items:center;justify-content:space-between;';

    const contador = document.createElement('span');
    contador.style.cssText = 'color:rgba(255,255,255,0.7);font-size:13px;font-family:sans-serif;';

    const btnCerrar = document.createElement('button');
    btnCerrar.type = 'button';
    btnCerrar.style.cssText = 'color:rgba(255,255,255,0.85);background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;font-size:13px;font-family:sans-serif;padding:0;';
    btnCerrar.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg> Cerrar';

    topBar.appendChild(contador);
    topBar.appendChild(btnCerrar);

    // ── Zona imagen + flechas ────────────────────────────────────────────────
    const fotoWrap = document.createElement('div');
    fotoWrap.style.cssText = 'position:relative;width:100%;display:flex;align-items:center;justify-content:center;';

    const foto = document.createElement('img');
    foto.alt = 'Foto del anuncio';
    foto.style.cssText = 'max-width:100%;max-height:80vh;object-fit:contain;border-radius:12px;box-shadow:0 25px 60px rgba(0,0,0,0.5);display:block;';

    const btnPrev = document.createElement('button');
    btnPrev.type = 'button';
    btnPrev.style.cssText = 'position:absolute;left:-48px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.12);border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:white;transition:background 0.15s;';
    btnPrev.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>';
    btnPrev.addEventListener('mouseenter', () => btnPrev.style.background = 'rgba(255,255,255,0.25)');
    btnPrev.addEventListener('mouseleave', () => btnPrev.style.background = 'rgba(255,255,255,0.12)');

    const btnNext = document.createElement('button');
    btnNext.type = 'button';
    btnNext.style.cssText = 'position:absolute;right:-48px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.12);border:none;border-radius:50%;width:40px;height:40px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:white;transition:background 0.15s;';
    btnNext.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
    btnNext.addEventListener('mouseenter', () => btnNext.style.background = 'rgba(255,255,255,0.25)');
    btnNext.addEventListener('mouseleave', () => btnNext.style.background = 'rgba(255,255,255,0.12)');

    fotoWrap.appendChild(btnPrev);
    fotoWrap.appendChild(foto);
    fotoWrap.appendChild(btnNext);

    // ── Miniaturas ───────────────────────────────────────────────────────────
    const thumbsRow = document.createElement('div');
    thumbsRow.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;justify-content:center;max-width:100%;';

    inner.appendChild(topBar);
    inner.appendChild(fotoWrap);
    inner.appendChild(thumbsRow);
    overlay.appendChild(inner);
    document.body.appendChild(overlay);

    // ── Lógica ───────────────────────────────────────────────────────────────
    function render() {
        foto.src = urls[idx];
        contador.textContent = urls.length > 1 ? `${idx + 1} / ${urls.length}` : '';
        btnPrev.style.display = urls.length > 1 ? 'flex' : 'none';
        btnNext.style.display = urls.length > 1 ? 'flex' : 'none';
        thumbsRow.style.display = urls.length > 1 ? 'flex' : 'none';
        thumbsRow.querySelectorAll('img').forEach((t, i) => {
            t.style.outline = i === idx ? '3px solid white' : '3px solid transparent';
            t.style.opacity = i === idx ? '1' : '0.55';
        });
    }

    function irA(i) {
        idx = (i + urls.length) % urls.length;
        render();
    }

    function abrir(lista, inicio) {
        urls = lista;
        idx  = inicio ?? 0;

        thumbsRow.innerHTML = '';
        if (lista.length > 1) {
            lista.forEach((u, i) => {
                const t = document.createElement('img');
                t.src = u;
                t.style.cssText = 'width:52px;height:52px;object-fit:cover;border-radius:6px;cursor:pointer;transition:opacity 0.15s,outline 0.15s;';
                t.addEventListener('click', () => irA(i));
                thumbsRow.appendChild(t);
            });
        }

        render();
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function cerrar() {
        overlay.style.display = 'none';
        foto.src = '';
        urls = [];
        document.body.style.overflow = '';
    }

    btnPrev.addEventListener('click', () => irA(idx - 1));
    btnNext.addEventListener('click', () => irA(idx + 1));
    overlay.addEventListener('click', cerrar);
    btnCerrar.addEventListener('click', cerrar);
    document.addEventListener('keydown', e => {
        if (overlay.style.display === 'none') return;
        if (e.key === 'Escape')     cerrar();
        if (e.key === 'ArrowLeft')  irA(idx - 1);
        if (e.key === 'ArrowRight') irA(idx + 1);
    });

    window.abrirCarouselAnuncio = abrir;
})();
</script>
