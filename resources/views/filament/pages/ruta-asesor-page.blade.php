<x-filament-panels::page>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @php
        $asesores = $this->getAsesores();
        $primerAsesor = $asesores->first();

        // Obtener todos los días disponibles (unión de todos los asesores)
        $todosLosDias = \App\Models\RoutePoint::selectRaw('DATE(timestamp) as fecha')
            ->groupByRaw('DATE(timestamp)')
            ->orderByDesc('fecha')
            ->limit(30)
            ->pluck('fecha')
            ->toArray();

        $puntosData = [];
        foreach ($asesores as $asesor) {
            $dias = \App\Models\RoutePoint::where('user_id', $asesor->id)
                ->selectRaw('DATE(timestamp) as fecha')
                ->groupByRaw('DATE(timestamp)')
                ->orderByDesc('fecha')
                ->limit(30)
                ->pluck('fecha')
                ->toArray();

            $puntosData[$asesor->id] = [];
            foreach ($dias as $dia) {
                $puntos = \App\Models\RoutePoint::where('user_id', $asesor->id)
                    ->whereDate('timestamp', $dia)
                    ->orderBy('timestamp')
                    ->get(['id', 'lat', 'lng', 'precision', 'velocidad', 'timestamp'])
                    ->map(fn($p) => [
                        'id'        => $p->id,
                        'lat'       => $p->lat,
                        'lng'       => $p->lng,
                        'precision' => $p->precision,
                        'velocidad' => $p->velocidad,
                        'hora'      => $p->timestamp->format('H:i:s'),
                        'timestamp' => $p->timestamp->toIso8601String(),
                    ])
                    ->toArray();
                $puntosData[$asesor->id][$dia] = $puntos;
            }
        }

        // Puntos de "todos" por día
        $puntosTodos = [];
        foreach ($todosLosDias as $dia) {
            $puntosTodos[$dia] = [];
            foreach ($asesores as $asesor) {
                if (isset($puntosData[$asesor->id][$dia])) {
                    $puntosTodos[$dia][] = [
                        'name' => $asesor->name,
                        'puntos' => $puntosData[$asesor->id][$dia],
                    ];
                }
            }
        }
    @endphp

    <script type="application/json" id="asesores-data">{!! json_encode($asesores->map(fn($a) => ['id' => $a->id, 'name' => $a->name])) !!}</script>
    <script type="application/json" id="puntos-data">{!! json_encode($puntosData) !!}</script>
    <script type="application/json" id="puntos-todos">{!! json_encode($puntosTodos) !!}</script>
    <script type="application/json" id="dias-todos">{!! json_encode($todosLosDias) !!}</script>

    <div class="space-y-6" x-data="rutasAsesores()">

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4 flex flex-wrap gap-4 items-end">

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Asesor</label>
                <select x-model="asesorId" @change="onAsesorChange()"
                    class="text-sm border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <option value="">Todos los asesores</option>
                    @foreach($asesores as $asesor)
                        <option value="{{ $asesor->id }}">{{ $asesor->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                <select x-model="fecha" @change="onFechaChange()"
                    :disabled="diasDisponibles.length === 0"
                    class="text-sm border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 disabled:opacity-50">
                    <option value="">Selecciona una fecha</option>
                    <template x-for="d in diasDisponibles" :key="d">
                        <option :value="d" x-text="d"></option>
                    </template>
                </select>
            </div>

            <div class="ml-auto flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" x-show="(asesorId !== '' && puntos.length > 0) || (asesorId === '' && puntosTodos.length > 0)">
                <template x-if="asesorId !== ''">
                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded" x-text="puntos.length + ' puntos'"></span>
                </template>
                <template x-if="asesorId === ''">
                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded" x-text="puntosTodos.flatMap(p => p.puntos).length + ' puntos'"></span>
                </template>
                <span x-show="distanciaKm > 0" x-text="distanciaKm.toFixed(1) + ' km'"></span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl overflow-hidden" style="height: 700px;">
            <div id="mapa-rutas" style="height: 100%; width: 100%;"></div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl p-4" x-show="asesorId !== '' && puntos.length > 0">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Detalle de la ruta</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-white/10">
                            <th class="pb-2">Hora</th>
                            <th class="pb-2">Lat</th>
                            <th class="pb-2">Lng</th>
                            <th class="pb-2">Precisión</th>
                            <th class="pb-2">Velocidad</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 dark:text-gray-300">
                        <template x-for="p in puntos" :key="p.id">
                            <tr class="border-b border-gray-100 dark:border-white/5 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="py-1.5" x-text="p.hora"></td>
                                <td class="py-1.5" x-text="p.lat.toFixed(5)"></td>
                                <td class="py-1.5" x-text="p.lng.toFixed(5)"></td>
                                <td class="py-1.5" x-text="p.precision + 'm'"></td>
                                <td class="py-1.5" x-text="(p.velocidad * 3.6).toFixed(1) + ' km/h'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
    function rutasAsesores() {
        const puntosData = JSON.parse(document.getElementById('puntos-data').textContent);
        const puntosTodos = JSON.parse(document.getElementById('puntos-todos').textContent);
        const diasTodos = JSON.parse(document.getElementById('dias-todos').textContent);

        return {
            mapa: null,
            polylines: [],
            markersLayer: null,
            puntos: [],
            puntosTodos: [],
            distanciaKm: 0,
            asesorId: '',
            fecha: '',
            diasDisponibles: [],

            init() {
                // Iniciar con "todos" seleccionado por defecto
                this.asesorId = '';
                this.polyline = null;
                this.diasDisponibles = diasTodos;
                if (this.diasDisponibles.length > 0) {
                    this.fecha = this.diasDisponibles[0];
                    this.puntosTodos = puntosTodos[this.fecha] || [];
                }
                this.$nextTick(() => this.iniciarMapa());
                if (this.puntosTodos.length > 0) {
                    this.$nextTick(() => this.renderRuta());
                }
            },

            iniciarMapa() {
                if (this.mapa) return;
                this.mapa = L.map('mapa-rutas').setView([19.4326, -99.1332], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 19,
                }).addTo(this.mapa);
                this.markersLayer = L.layerGroup().addTo(this.mapa);
            },

            onAsesorChange() {
                this.puntos = [];
                this.puntosTodos = [];
                this.fecha = '';
                this.distanciaKm = 0;

                if (this.asesorId === '') {
                    // Todos los asesores
                    this.diasDisponibles = diasTodos;
                } else {
                    // Asesor específico
                    this.diasDisponibles = Object.keys(puntosData[this.asesorId] || {});
                }

                if (this.diasDisponibles.length > 0) {
                    this.fecha = this.diasDisponibles[0];
                    this.onFechaChange();
                }
            },

            onFechaChange() {
                if (!this.fecha) {
                    this.puntos = [];
                    this.puntosTodos = [];
                    this.renderRuta();
                    return;
                }

                if (this.asesorId === '') {
                    // Todos los asesores
                    this.puntos = [];
                    this.puntosTodos = puntosTodos[this.fecha] || [];
                } else {
                    // Asesor específico
                    this.puntos = puntosData[this.asesorId]?.[this.fecha] || [];
                    this.puntosTodos = [];
                }
                this.renderRuta();
                this.calcularDistancia();
            },

            renderRuta() {
                if (!this.mapa) {
                    this.$nextTick(() => this.renderRuta());
                    return;
                }

                // Limpiar polylines anteriores
                this.polylines.forEach(p => this.mapa.removeLayer(p));
                this.polylines = [];
                this.markersLayer.clearLayers();

                // Caso: todos los asesores
                if (this.puntosTodos.length > 0) {
                    const colores = ['#dc2626', '#2563eb', '#16a34a', '#9333ea', '#ea580c', '#0891b2', '#db2777', '#65a30d'];
                    let bounds = [];

                    this.puntosTodos.forEach((asesor, idx) => {
                        const color = colores[idx % colores.length];
                        const coords = asesor.puntos.map(p => [p.lat, p.lng]);
                        const polyline = L.polyline(coords, {
                            color: color,
                            weight: 4,
                            opacity: 0.9,
                        }).addTo(this.mapa);
                        this.polylines.push(polyline);
                        bounds.push(...coords);

                        // Marcadores de inicio y fin
                        const start = asesor.puntos[0];
                        const end = asesor.puntos[asesor.puntos.length - 1];

                        const nameTrunc = asesor.name.length > 8 ? asesor.name.substring(0, 7) + '…' : asesor.name;
                        const startIcon = L.divIcon({
                            className: '',
                            html: `<div style="background:${color};width:auto;min-width:120px;height:28px;border-radius:14px;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;font-size:10px;color:white;font-weight:bold;padding:0 8px;white-space:nowrap;">${nameTrunc} INICIO</div>`,
                            iconSize: [120, 28],
                            iconAnchor: [60, 14],
                        });

                        const endIcon = L.divIcon({
                            className: '',
                            html: `<div style="background:${color};width:auto;min-width:100px;height:28px;border-radius:14px;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;font-size:10px;color:white;font-weight:bold;padding:0 8px;white-space:nowrap;">${nameTrunc} FIN</div>`,
                            iconSize: [100, 28],
                            iconAnchor: [50, 14],
                        });

                        L.marker([start.lat, start.lng], { icon: startIcon })
                            .bindPopup(`<b>${asesor.name}</b><br>Inicio: ${start.hora}`)
                            .addTo(this.markersLayer);

                        L.marker([end.lat, end.lng], { icon: endIcon })
                            .bindPopup(`<b>${asesor.name}</b><br>Fin: ${end.hora}`)
                            .addTo(this.markersLayer);
                    });

                    if (bounds.length > 0) {
                        this.mapa.fitBounds(bounds, { padding: [40, 40] });
                    }
                    return;
                }

                // Caso: un solo asesor
                if (this.puntos.length === 0) return;

                const coords = this.puntos.map(p => [p.lat, p.lng]);
                this.polyline = L.polyline(coords, {
                    color: '#dc2626',
                    weight: 4,
                    opacity: 0.9,
                }).addTo(this.mapa);
                this.polylines.push(this.polyline);

                this.mapa.fitBounds(this.polyline.getBounds(), { padding: [40, 40] });

                const start = this.puntos[0];
                const end = this.puntos[this.puntos.length - 1];

                const startIcon = L.divIcon({
                    className: '',
                    html: '<div style="background:#16a34a;width:auto;min-width:70px;height:24px;border-radius:12px;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;font-size:11px;color:white;font-weight:bold;padding:0 8px;">INICIO</div>',
                    iconSize: [70, 24],
                    iconAnchor: [35, 12],
                });

                const endIcon = L.divIcon({
                    className: '',
                    html: '<div style="background:#dc2626;width:auto;min-width:50px;height:24px;border-radius:12px;border:2px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;font-size:11px;color:white;font-weight:bold;padding:0 8px;">FIN</div>',
                    iconSize: [50, 24],
                    iconAnchor: [25, 12],
                });

                L.marker([start.lat, start.lng], { icon: startIcon })
                    .bindPopup(`<b>Inicio</b><br>${start.hora}<br>${start.lat.toFixed(5)}, ${start.lng.toFixed(5)}`)
                    .addTo(this.markersLayer);

                L.marker([end.lat, end.lng], { icon: endIcon })
                    .bindPopup(`<b>Fin</b><br>${end.hora}<br>${end.lat.toFixed(5)}, ${end.lng.toFixed(5)}`)
                    .addTo(this.markersLayer);
            },

            calcularDistancia() {
                if (this.puntos.length < 2) {
                    this.distanciaKm = 0;
                    return;
                }

                let total = 0;
                for (let i = 1; i < this.puntos.length; i++) {
                    total += this.haversine(
                        this.puntos[i-1].lat, this.puntos[i-1].lng,
                        this.puntos[i].lat, this.puntos[i].lng
                    );
                }
                this.distanciaKm = total;
            },

            haversine(lat1, lng1, lat2, lng2) {
                const R = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLng = (lng2 - lng1) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLng/2) * Math.sin(dLng/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            },
        };
    }
    </script>

</x-filament-panels::page>
