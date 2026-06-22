<x-filament-panels::page>
    <div style="padding-bottom: 6rem;">
    <div>
        {{ $this->form }}
    </div>

    {{-- Zona de carga con webkitdirectory via Alpine --}}
    <div
        class="mt-4"
        x-data="{
            archivos: [],
            arrastrando: false,
            subiendo: false,
            progreso: 0,
            error: null,

            seleccionar(event) {
                const extensionesValidas = ['pdf','jpg','jpeg','png','webp','heic','gif','doc','docx','xls','xlsx'];
                const files = Array.from(event.target.files).filter(f => {
                    // Ignorar archivos del sistema macOS/Windows
                    if (f.name.startsWith('.') || f.name.startsWith('_')) return false;
                    const ext = f.name.split('.').pop().toLowerCase();
                    return extensionesValidas.includes(ext);
                });
                if (!files.length) return;
                this.error = null;
                this.archivos = files.map(f => ({
                    nombre: f.name,
                    ruta: f.webkitRelativePath || f.name,
                    size: (f.size / 1024).toFixed(1) + ' KB',
                    tipo: f.name.split('.').pop().toUpperCase(),
                    file: f,
                }));
            },

            categoriaColor(ruta) {
                const r = ruta.toUpperCase();
                if (r.includes('/SOFOM/ACREDITAD')) return 'background:#6d28d9;color:#fff;';
                if (r.includes('/SOFOM/VENDEDOR'))  return 'background:#7c3aed;color:#fff;';
                if (r.includes('/SOFOM/VIVIENDA'))  return 'background:#5b21b6;color:#fff;';
                if (r.includes('/SOFOM'))           return 'background:#7e22ce;color:#fff;';
                if (r.includes('/NOTARIA'))         return 'background:#b91c1c;color:#fff;';
                if (r.includes('/ACREDITAD'))       return 'background:#1d4ed8;color:#fff;';
                if (r.includes('/VENDEDOR'))        return 'background:#b45309;color:#fff;';
                if (r.includes('/VIVIENDA'))        return 'background:#15803d;color:#fff;';
                if (r.includes('/AVALUO'))          return 'background:#c2410c;color:#fff;';
                if (r.includes('/CATASTRO'))        return 'background:#0f766e;color:#fff;';
                return 'background:#374151;color:#fff;';
            },

            categoriaLabel(ruta) {
                const r = ruta.toUpperCase();
                if (r.includes('/SOFOM/ACREDITAD')) return 'SOFOM/ACREDITADA';
                if (r.includes('/SOFOM/VENDEDOR'))  return 'SOFOM/VENDEDOR';
                if (r.includes('/SOFOM/VIVIENDA'))  return 'SOFOM/VIVIENDA';
                if (r.includes('/SOFOM'))           return 'SOFOM';
                if (r.includes('/NOTARIA'))         return 'NOTARÍA';
                if (r.includes('/ACREDITAD'))       return 'ACREDITADA';
                if (r.includes('/VENDEDOR'))        return 'VENDEDOR';
                if (r.includes('/VIVIENDA'))        return 'VIVIENDA';
                if (r.includes('/AVALUO'))          return 'AVALÚO';
                if (r.includes('/CATASTRO'))        return 'CATASTRO';
                return 'GENERAL';
            },

            agrupadosPorCategoria() {
                const grupos = {};
                this.archivos.forEach(a => {
                    const cat = this.categoriaLabel(a.ruta);
                    if (!grupos[cat]) grupos[cat] = [];
                    grupos[cat].push(a);
                });
                return grupos;
            },

            limpiar() {
                this.archivos = [];
                this.error = null;
                this.progreso = 0;
                this.$refs.inputDir.value = '';
            },

            uploadFile(file, ruta) {
                return new Promise((resolve, reject) => {
                    $wire.upload(
                        'archivosFilepond',
                        file,
                        (tmpPath) => resolve({ tmp_path: tmpPath, ruta: ruta }),
                        (error)   => reject(new Error('Error subiendo ' + file.name + ': ' + error)),
                        (event)   => {}
                    );
                });
            },

            async procesarCarpeta() {
                if (!this.archivos.length) {
                    this.error = 'Selecciona una carpeta primero.';
                    return;
                }

                this.subiendo = true;
                this.progreso = 0;
                this.error    = null;

                try {
                    const total    = this.archivos.length;
                    const subidos  = [];

                    for (let i = 0; i < this.archivos.length; i++) {
                        const a = this.archivos[i];

                        await new Promise((resolve, reject) => {
                            $wire.upload(
                                'archivosFilepond.' + i,
                                a.file,
                                (tmpPath) => { subidos.push({ tmp: tmpPath, ruta: a.ruta }); resolve(); },
                                (err)     => reject(new Error('Error subiendo ' + a.nombre)),
                                ()        => {}
                            );
                        });

                        this.progreso = Math.round(((i + 1) / total) * 80);
                    }

                    this.progreso = 85;

                    // Pasar rutas a Livewire y llamar crear
                    await $wire.set('rutasArchivos', subidos.map(s => s.ruta));
                    await $wire.set('tmpPaths', subidos.map(s => s.tmp));
                    await $wire.call('crear');

                } catch (e) {
                    this.error   = e.message;
                    this.subiendo = false;
                    this.progreso = 0;
                }
            },
        }"
    >
        {{-- Input oculto con webkitdirectory — SIN wire:model --}}
        <input
            type="file"
            x-ref="inputDir"
            webkitdirectory
            multiple
            class="hidden"
            @change="seleccionar($event)"
        >

        {{-- Drop zone --}}
        <div
            class="rounded-xl border-2 border-dashed transition-colors cursor-pointer"
            :class="arrastrando ? 'border-primary-500 bg-primary-50' : 'border-gray-300 bg-gray-50 hover:border-primary-400'"
            @dragover.prevent="arrastrando = true"
            @dragleave.prevent="arrastrando = false"
            @drop.prevent="arrastrando = false"
            @click="$refs.inputDir.click()"
            style="padding: 2.5rem 1.5rem; text-align: center;"
        >
            <template x-if="!archivos.length">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3" style="width:48px;height:48px;color:#9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                    </svg>
                    <p class="text-sm font-semibold text-gray-700">Haz clic para seleccionar la carpeta del acreditado</p>
                    <p class="text-xs text-gray-400 mt-1">O arrastra aquí · PDF, JPG, PNG · máx 20 MB por archivo</p>
                    <p class="text-xs text-gray-400 mt-2">Subcarpetas válidas: <strong>ACREDITADA · VENDEDOR · VIVIENDA · SOFOM · NOTARIA</strong></p>
                </div>
            </template>

            <template x-if="archivos.length">
                <div @click.stop>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-gray-700">
                            <span x-text="archivos.length"></span> archivos seleccionados
                        </p>
                        <button type="button" class="text-xs text-gray-400 hover:text-red-500" @click="limpiar()">
                            Limpiar
                        </button>
                    </div>
                    <template x-for="[cat, items] in Object.entries(agrupadosPorCategoria())" :key="cat">
                        <div class="mb-3 text-left">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1" x-text="cat"></p>
                            <div class="space-y-1">
                                <template x-for="archivo in items" :key="archivo.ruta">
                                    <div class="flex items-center gap-2 px-3 py-1.5 bg-white rounded-lg border border-gray-200 text-sm">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0" :style="categoriaColor(archivo.ruta)" x-text="archivo.tipo"></span>
                                        <span class="flex-1 text-gray-700 truncate" x-text="archivo.nombre"></span>
                                        <span class="text-xs text-gray-400 flex-shrink-0" x-text="archivo.size"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Error Alpine --}}
        <template x-if="error">
            <p class="mt-2 text-sm text-red-600 font-medium" x-text="error"></p>
        </template>

        {{-- Progreso Alpine --}}
        <template x-if="subiendo">
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-xs text-gray-500">
                    <span x-text="progreso < 85 ? `Subiendo archivos... (${progreso}%)` : 'Creando expediente...'"></span>
                    <span x-text="progreso + '%'"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300" style="background-color: rgb(var(--primary-600));" :style="`width: ${progreso}%`"></div>
                </div>
            </div>
        </template>

        {{-- Botones --}}
        <div class="mt-6 flex justify-end gap-3">
            <a
                href="{{ \App\Filament\Resources\ExpedienteResource::getUrl('index') }}"
                class="inline-flex items-center justify-center gap-1 font-semibold rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 bg-white shadow-sm hover:bg-gray-50"
            >
                Cancelar
            </a>

            <button
                type="button"
                @click="procesarCarpeta()"
                :disabled="subiendo || !archivos.length"
                class="inline-flex items-center justify-center gap-2 font-semibold rounded-lg px-4 py-2 text-sm shadow-sm text-white transition-opacity"
                :class="(subiendo || !archivos.length) ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                style="background-color: rgb(var(--primary-600));"
            >
                <svg x-show="!subiendo" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                <svg x-show="subiendo" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                <span x-show="!subiendo">Crear expediente</span>
                <span x-show="subiendo">Procesando...</span>
            </button>
        </div>
    </div>
    </div>
</x-filament-panels::page>
