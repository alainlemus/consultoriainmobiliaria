<x-filament-panels::page>

    {{-- Leaflet CSS (auto-hospedado, ver public/vendor/leaflet) --}}
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />

    @php
        $stats        = $this->getStats();
        $asesores     = $this->getAsesores();
        $esSuperAdmin = $this->esSuperAdmin();
    @endphp

    <div class="space-y-6" x-data="mapaVisitas()" x-init="init()">

        {{-- JSON de ubicaciones --}}
        <script type="application/json" id="ubicaciones-data">{!! $this->getUbicacionesJson() !!}</script>
        {{-- JSON de anuncios --}}
        <script type="application/json" id="anuncios-data">{!! $this->getAnunciosJson() !!}</script>

        {{-- Stats: ahora 6 columnas con anuncios --}}
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex items-center gap-3">
                <div class="rounded-full p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-map-pin" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total visitas</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex items-center gap-3">
                <div class="rounded-full p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-o-home" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['clientes'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Visitas clientes</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex items-center gap-3">
                <div class="rounded-full p-2 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400">
                    <x-filament::icon icon="heroicon-o-building-office" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['props'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Propiedades</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex items-center gap-3">
                <div class="rounded-full p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['escuelas'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Escuelas</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex items-center gap-3">
                <div class="rounded-full p-2 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400">
                    <x-filament::icon icon="heroicon-o-users" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['asesores'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $esSuperAdmin ? 'Asesores activos' : 'Asesor' }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex items-center gap-3">
                <div class="rounded-full p-2 bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400">
                    <x-filament::icon icon="heroicon-o-megaphone" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['anuncios'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Anuncios activos</p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex flex-wrap gap-3 items-center">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por:</span>

            {{-- Tipo visitas --}}
            <div class="flex gap-2 flex-wrap">
                <button @click="setFiltroTipo('todos')"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition cursor-pointer"
                    :class="filtroTipo === 'todos' ? 'bg-gray-800 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'">
                    Todos
                </button>
                <button @click="setFiltroTipo('visita_cliente')"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition cursor-pointer"
                    :class="filtroTipo === 'visita_cliente' ? 'bg-amber-500 text-white' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'">
                    🏠 Clientes
                </button>
                <button @click="setFiltroTipo('propiedad')"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition cursor-pointer"
                    :class="filtroTipo === 'propiedad' ? 'bg-purple-600 text-white' : 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400'">
                    🏢 Propiedades
                </button>
                <button @click="setFiltroTipo('escuela')"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition cursor-pointer"
                    :class="filtroTipo === 'escuela' ? 'bg-blue-600 text-white' : 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400'">
                    🏫 Escuelas
                </button>
                <button @click="toggleAnuncios()"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition cursor-pointer"
                    :class="mostrarAnuncios ? 'bg-orange-500 text-white' : 'bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400'">
                    📢 Anuncios
                </button>
            </div>

            {{-- Asesor (solo super_admin) --}}
            @if($esSuperAdmin && $asesores->count() > 0)
            <select x-model="filtroAsesor" @change="aplicarFiltros()"
                class="text-xs border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                <option value="">Todos los asesores</option>
                @foreach($asesores as $a)
                <option value="{{ $a->id }}">{{ $a->name }}</option>
                @endforeach
            </select>
            @endif

            {{-- Contador --}}
            <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">
                <span x-text="marcadoresFiltrados.length"></span> puntos visibles
            </span>
        </div>

        {{-- Mapa --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden" style="height: 700px;">
            <div id="mapa-visitas" style="height: 100%; width: 100%; z-index: 0;"></div>
        </div>

        {{-- Leyenda --}}
        <div class="flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-400 px-1">
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded-full bg-amber-500"></span> Visita cliente
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded-full bg-purple-600"></span> Propiedad
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span> Escuela
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-block w-3 h-3 rounded-full bg-orange-500"></span> Anuncio
            </div>
            <span class="text-gray-300 dark:text-gray-600">|</span>
            <span class="font-medium text-gray-500 dark:text-gray-400">Semáforo escuelas:</span>
            <div class="flex items-center gap-1">🟢 <span>Hay clientes</span></div>
            <div class="flex items-center gap-1">🟡 <span>Sin clientes</span></div>
            <div class="flex items-center gap-1">🔴 <span>Acceso denegado</span></div>
            <p class="ml-auto text-gray-400 dark:text-gray-500">Haz clic en un marcador para ver detalles · Zoom con scroll</p>
        </div>

    </div>

    {{-- Leaflet JS (auto-hospedado, ver public/vendor/leaflet) --}}
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

    <script>
    // ── Modal carousel de fotos (JS puro, montado en body) ──
    (function () {
        let urls   = [];
        let idx    = 0;

        // Overlay
        const overlay = document.createElement('div');
        overlay.id = 'foto-modal-overlay';
        overlay.style.cssText = 'display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.88);align-items:center;justify-content:center;padding:16px;cursor:zoom-out;';

        // Contenedor central
        const inner = document.createElement('div');
        inner.style.cssText = 'position:relative;max-width:900px;width:100%;cursor:default;display:flex;flex-direction:column;align-items:center;gap:12px;';
        inner.addEventListener('click', e => e.stopPropagation());

        // Barra superior: contador + cerrar
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

        // Zona imagen + flechas
        const fotoWrap = document.createElement('div');
        fotoWrap.style.cssText = 'position:relative;width:100%;display:flex;align-items:center;justify-content:center;';

        const foto = document.createElement('img');
        foto.alt = 'Foto ampliada';
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

        // Miniaturas
        const thumbsRow = document.createElement('div');
        thumbsRow.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;justify-content:center;max-width:100%;';

        inner.appendChild(topBar);
        inner.appendChild(fotoWrap);
        inner.appendChild(thumbsRow);
        overlay.appendChild(inner);
        document.body.appendChild(overlay);

        function render() {
            foto.src = urls[idx];
            contador.textContent = urls.length > 1 ? `${idx + 1} / ${urls.length}` : '';
            btnPrev.style.display = urls.length > 1 ? 'flex' : 'none';
            btnNext.style.display = urls.length > 1 ? 'flex' : 'none';
            thumbsRow.style.display = urls.length > 1 ? 'flex' : 'none';
            // Actualizar estado activo de miniaturas
            thumbsRow.querySelectorAll('img').forEach((t, i) => {
                t.style.outline = i === idx ? '3px solid white' : '3px solid transparent';
                t.style.opacity = i === idx ? '1' : '0.55';
            });
        }

        function irA(i) {
            idx = (i + urls.length) % urls.length;
            render();
        }

        function abrirModal(lista, inicio) {
            urls = lista;
            idx  = inicio ?? 0;

            // Construir miniaturas
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

        function cerrarModal() {
            overlay.style.display = 'none';
            foto.src = '';
            urls = [];
            document.body.style.overflow = '';
        }

        btnPrev.addEventListener('click', () => irA(idx - 1));
        btnNext.addEventListener('click', () => irA(idx + 1));
        overlay.addEventListener('click', cerrarModal);
        btnCerrar.addEventListener('click', cerrarModal);
        document.addEventListener('keydown', e => {
            if (overlay.style.display === 'none') return;
            if (e.key === 'Escape')      cerrarModal();
            if (e.key === 'ArrowLeft')   irA(idx - 1);
            if (e.key === 'ArrowRight')  irA(idx + 1);
        });

        window.abrirFotoModal = abrirModal;
    })();

    function mapaVisitas() {
        const datos    = JSON.parse(document.getElementById('ubicaciones-data').textContent);
        const anuncios = JSON.parse(document.getElementById('anuncios-data').textContent);
        return {
            mapa:                null,
            todos:               datos,
            todosAnuncios:       anuncios,
            marcadoresFiltrados: datos,
            filtroTipo:          'todos',
            filtroAsesor:        '',
            mostrarAnuncios:     true,
            capas:               [],
            capasAnuncios:       [],

            init() {
                this.$nextTick(() => this.iniciarMapa());
            },

            iniciarMapa() {
                this.mapa = L.map('mapa-visitas').setView([19.4326, -99.1332], 11);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(this.mapa);

                this.renderMarcadores();
                this.renderAnuncios();
            },

            iconoPara(tipo, semaforo) {
                const colores = { visita_cliente: '#f59e0b', propiedad: '#7c3aed', escuela: '#3b82f6' };
                const emojis  = { visita_cliente: '🏠', propiedad: '🏢', escuela: '🏫' };
                const color   = colores[tipo] ?? '#6b7280';
                const emoji   = emojis[tipo]  ?? '📍';

                // Para escuelas: anillo de color del semáforo alrededor del icono
                const semaforoColor = tipo === 'escuela'
                    ? ({ verde: '#22c55e', amarillo: '#f59e0b', rojo: '#ef4444' }[semaforo] ?? '#f59e0b')
                    : null;

                const borderStyle = semaforoColor
                    ? `border: 3px solid ${semaforoColor}; box-shadow: 0 0 0 2px ${semaforoColor}44, 0 2px 8px rgba(0,0,0,0.3);`
                    : 'border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);';

                return L.divIcon({
                    className: '',
                    html: `<div style="
                        background:${color};
                        width:36px;height:36px;border-radius:50%;
                        display:flex;align-items:center;justify-content:center;
                        font-size:18px;
                        ${borderStyle}
                    ">${emoji}</div>`,
                    iconSize:   [36, 36],
                    iconAnchor: [18, 18],
                    popupAnchor:[0, -20],
                });
            },

            renderMarcadores() {
                // Limpiar capas anteriores
                this.capas.forEach(c => this.mapa.removeLayer(c));
                this.capas = [];

                if (this.marcadoresFiltrados.length === 0) return;

                const bounds = [];

                this.marcadoresFiltrados.forEach(u => {
                    const m = L.marker([u.latitud, u.longitud], { icon: this.iconoPara(u.tipo, u.semaforo) });

                    const tipoLabel = { visita_cliente: 'Visita cliente', propiedad: 'Propiedad', escuela: 'Escuela' }[u.tipo] ?? u.tipo;

                    // Crear popup con elemento DOM real (evita sanitización de Leaflet)
                    const popupEl = document.createElement('div');
                    popupEl.style.cssText = 'font-family:sans-serif;font-size:13px;min-width:180px;max-width:260px;';

                    const titulo = document.createElement('p');
                    titulo.style.cssText = 'font-weight:700;font-size:14px;margin:0 0 6px;color:#111827';
                    titulo.textContent = tipoLabel;
                    popupEl.appendChild(titulo);

                    // Semáforo (solo escuelas)
                    if (u.tipo === 'escuela' && u.semaforo) {
                        const semaforoEmoji = { verde: '🟢', amarillo: '🟡', rojo: '🔴' }[u.semaforo] ?? '🟡';
                        const semaforoLabel = { verde: 'Hay maestros clientes', amarillo: 'Sin clientes aún', rojo: 'Acceso denegado' }[u.semaforo] ?? '';
                        const ps = document.createElement('p');
                        ps.style.cssText = 'margin:2px 0 6px;color:#374151;font-weight:600';
                        ps.textContent = `${semaforoEmoji} ${semaforoLabel}`;
                        popupEl.appendChild(ps);
                        if (u.semaforo_notas) {
                            const pn = document.createElement('p');
                            pn.style.cssText = 'margin:2px 0 4px;color:#6b7280;font-size:11px;font-style:italic';
                            pn.textContent = u.semaforo_notas;
                            popupEl.appendChild(pn);
                        }
                    }

                    if (u.contacto) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:2px 0;color:#374151';
                        p.innerHTML = `<b>Cliente:</b> ${u.contacto}`;
                        popupEl.appendChild(p);
                    }
                    if (u.nombre_lugar) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:2px 0;color:#374151';
                        p.innerHTML = `<b>Nombre:</b> ${u.nombre_lugar}`;
                        popupEl.appendChild(p);
                    }
                    if (u.asesor) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:2px 0;color:#374151';
                        p.innerHTML = `<b>Asesor:</b> ${u.asesor}`;
                        popupEl.appendChild(p);
                    }
                    if (u.notas) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:4px 0 2px;color:#6b7280;font-style:italic';
                        p.textContent = `"${u.notas}"`;
                        popupEl.appendChild(p);
                    }
                    if (u.visitado_en) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:4px 0 0;color:#9ca3af;font-size:11px';
                        p.textContent = `📅 ${u.visitado_en}`;
                        popupEl.appendChild(p);
                    }

                    if (u.fotos && u.fotos.length > 0) {
                        const grid = document.createElement('div');
                        grid.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;margin-top:8px';
                        const listaUrls = u.fotos.map(f => f.url);
                        u.fotos.forEach((f, i) => {
                            const img = document.createElement('img');
                            img.src = f.url;
                            img.style.cssText = 'width:72px;height:72px;object-fit:cover;border-radius:6px;cursor:pointer;';
                            img.addEventListener('click', () => window.abrirFotoModal(listaUrls, i));
                            grid.appendChild(img);
                        });
                        popupEl.appendChild(grid);
                    }

                    m.bindPopup(popupEl, { maxWidth: 300 });

                    m.addTo(this.mapa);
                    this.capas.push(m);
                    bounds.push([u.latitud, u.longitud]);
                });

                // Ajustar vista para mostrar todos los puntos
                if (bounds.length > 0) {
                    this.mapa.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
                }
            },

            setFiltroTipo(tipo) {
                this.filtroTipo = tipo;
                this.aplicarFiltros();
            },

            aplicarFiltros() {
                this.marcadoresFiltrados = this.todos.filter(u => {
                    const pasaTipo   = this.filtroTipo === 'todos' || u.tipo === this.filtroTipo;
                    const pasaAsesor = this.filtroAsesor === ''      || String(u.asesor_id) === String(this.filtroAsesor);
                    return pasaTipo && pasaAsesor;
                });
                this.renderMarcadores();
            },

            toggleAnuncios() {
                this.mostrarAnuncios = !this.mostrarAnuncios;
                this.renderAnuncios();
            },

            renderAnuncios() {
                // Limpiar capa anterior
                this.capasAnuncios.forEach(c => this.mapa.removeLayer(c));
                this.capasAnuncios = [];

                if (!this.mostrarAnuncios) return;

                const TIPO_EMOJI = {
                    lona: '📢', hoja_tienda: '🏪', hoja_poste: '📌', volante: '📄', otro: '📣'
                };

                this.todosAnuncios.forEach(a => {
                    const emoji   = TIPO_EMOJI[a.tipo] ?? '📣';
                    const opacity = a.estado === 'retirado' ? 0.4 : 1;

                    const icon = L.divIcon({
                        className: '',
                        html: `<div style="
                            background:#f97316;
                            width:34px;height:34px;border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            font-size:17px;
                            box-shadow:0 2px 8px rgba(0,0,0,0.3);
                            border:2px solid white;
                            opacity:${opacity};
                        ">${emoji}</div>`,
                        iconSize:   [34, 34],
                        iconAnchor: [17, 17],
                        popupAnchor:[0, -20],
                    });

                    const m = L.marker([a.latitud, a.longitud], { icon });

                    const popupEl = document.createElement('div');
                    popupEl.style.cssText = 'font-family:sans-serif;font-size:13px;min-width:180px;max-width:260px;';

                    const titulo = document.createElement('p');
                    titulo.style.cssText = 'font-weight:700;font-size:14px;margin:0 0 4px;color:#111827';
                    titulo.textContent = `${emoji} Anuncio — ${(a.tipo || '').replace('_', ' ')}`;
                    popupEl.appendChild(titulo);

                    if (a.estado === 'retirado') {
                        const badge = document.createElement('span');
                        badge.style.cssText = 'display:inline-block;background:#fee2e2;color:#dc2626;font-size:10px;font-weight:700;padding:1px 6px;border-radius:4px;margin-bottom:6px';
                        badge.textContent = 'RETIRADO';
                        popupEl.appendChild(badge);
                    }

                    if (a.asesor) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:2px 0;color:#374151';
                        p.innerHTML = `<b>Asesor:</b> ${a.asesor}`;
                        popupEl.appendChild(p);
                    }
                    if (a.direccion || a.colonia) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:2px 0;color:#374151';
                        p.textContent = [a.direccion, a.colonia, a.municipio].filter(Boolean).join(', ');
                        popupEl.appendChild(p);
                    }
                    if (a.descripcion) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:4px 0 2px;color:#6b7280;font-style:italic';
                        p.textContent = `"${a.descripcion}"`;
                        popupEl.appendChild(p);
                    }
                    if (a.colocado_en) {
                        const p = document.createElement('p');
                        p.style.cssText = 'margin:4px 0 0;color:#9ca3af;font-size:11px';
                        p.textContent = `📅 Colocado: ${a.colocado_en}`;
                        popupEl.appendChild(p);
                    }

                    if (a.fotos && a.fotos.length > 0) {
                        const grid = document.createElement('div');
                        grid.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;margin-top:8px';
                        const listaUrls = a.fotos.map(f => f.url);
                        a.fotos.forEach((f, i) => {
                            const img = document.createElement('img');
                            img.src = f.url;
                            img.style.cssText = 'width:72px;height:72px;object-fit:cover;border-radius:6px;cursor:pointer;';
                            img.addEventListener('click', () => window.abrirFotoModal(listaUrls, i));
                            grid.appendChild(img);
                        });
                        popupEl.appendChild(grid);
                    }

                    m.bindPopup(popupEl, { maxWidth: 300 });
                    m.addTo(this.mapa);
                    this.capasAnuncios.push(m);
                });
            },
        };
    }
    </script>

</x-filament-panels::page>
