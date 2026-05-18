<x-filament-panels::page>

    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @php
        $stats     = $this->getStats();
        $asesores  = $this->getAsesores();
    @endphp

    <div class="space-y-6" x-data="mapaVisitas({{ $this->getUbicacionesJson() }})" x-init="init()">

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex items-center gap-3">
                <div class="rounded-full p-2 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                    <x-filament::icon icon="heroicon-o-map-pin" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total registros</p>
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
                <div class="rounded-full p-2 bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400">
                    <x-filament::icon icon="heroicon-o-users" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['asesores'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Asesores activos</p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex flex-wrap gap-3 items-center">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por:</span>

            {{-- Tipo --}}
            <div class="flex gap-2">
                <button @click="setFiltroTipo('todos')"
                    :class="filtroTipo === 'todos' ? 'bg-gray-800 dark:bg-white text-white dark:text-gray-900' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition">
                    Todos
                </button>
                <button @click="setFiltroTipo('visita_cliente')"
                    :class="filtroTipo === 'visita_cliente' ? 'bg-amber-500 text-white' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition">
                    🏠 Clientes
                </button>
                <button @click="setFiltroTipo('propiedad')"
                    :class="filtroTipo === 'propiedad' ? 'bg-purple-600 text-white' : 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400'"
                    class="text-xs font-medium px-3 py-1.5 rounded-full transition">
                    🏢 Propiedades
                </button>
            </div>

            {{-- Asesor --}}
            @if($asesores->count() > 0)
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
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden" style="height: 560px;">
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
            <p class="ml-auto text-gray-400 dark:text-gray-500">Haz clic en un marcador para ver detalles · Zoom con scroll</p>
        </div>

    </div>

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    function mapaVisitas(datos) {
        return {
            mapa:               null,
            todos:              datos,
            marcadoresFiltrados: datos,
            filtroTipo:         'todos',
            filtroAsesor:       '',
            capas:              [],

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
            },

            iconoPara(tipo) {
                const color  = tipo === 'visita_cliente' ? '#f59e0b' : '#7c3aed';
                const emoji  = tipo === 'visita_cliente' ? '🏠' : '🏢';
                return L.divIcon({
                    className: '',
                    html: `<div style="
                        background:${color};
                        width:36px;height:36px;border-radius:50%;
                        display:flex;align-items:center;justify-content:center;
                        font-size:18px;
                        box-shadow:0 2px 8px rgba(0,0,0,0.3);
                        border:2px solid white;
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
                    const m = L.marker([u.latitud, u.longitud], { icon: this.iconoPara(u.tipo) });

                    const tipoLabel = u.tipo === 'visita_cliente' ? 'Visita cliente' : 'Propiedad';
                    const contacto  = u.contacto  ? `<p style="margin:2px 0;color:#374151"><b>Cliente:</b> ${u.contacto}</p>`  : '';
                    const asesor    = u.asesor     ? `<p style="margin:2px 0;color:#374151"><b>Asesor:</b> ${u.asesor}</p>`     : '';
                    const notas     = u.notas      ? `<p style="margin:4px 0 2px;color:#6b7280;font-style:italic">"${u.notas}"</p>` : '';
                    const fecha     = u.visitado_en? `<p style="margin:4px 0 0;color:#9ca3af;font-size:11px">📅 ${u.visitado_en}</p>` : '';

                    m.bindPopup(`
                        <div style="font-family:sans-serif;font-size:13px;min-width:180px;max-width:240px;">
                            <p style="font-weight:700;font-size:14px;margin:0 0 6px;color:#111827">${tipoLabel}</p>
                            ${contacto}${asesor}${notas}${fecha}
                        </div>
                    `);

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
                    const pasaTipo   = this.filtroTipo   === 'todos' || u.tipo      === this.filtroTipo;
                    const pasaAsesor = this.filtroAsesor === ''      || String(u.asesor_id) === String(this.filtroAsesor);
                    return pasaTipo && pasaAsesor;
                });
                this.renderMarcadores();
            },
        };
    }
    </script>

</x-filament-panels::page>
