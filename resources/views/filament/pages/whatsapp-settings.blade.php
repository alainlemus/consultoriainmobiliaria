<x-filament-panels::page>

    {{-- ── Formulario principal ── --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4 flex items-center gap-3">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                Guardar y actualizar webhook
            </x-filament::button>

            <x-filament::button
                type="button"
                color="warning"
                size="lg"
                icon="heroicon-o-cpu-chip"
                wire:click="detectarHostname"
            >
                Auto-detectar hostname
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                size="lg"
                icon="heroicon-o-arrow-path"
                wire:click="cargarSesiones"
            >
                Recargar sesiones
            </x-filament::button>
        </div>
    </form>

    {{-- ── Sesiones de OpenWA ── --}}
    <div class="mt-8 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Sesiones de WhatsApp en OpenWA
        </h2>

        {{-- Crear nueva sesión --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 flex items-end gap-3">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Nombre de la nueva sesión
                </label>
                <input
                    type="text"
                    wire:model="nuevaSesionNombre"
                    placeholder="ej: negocio, ventas, soporte"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none"
                />
            </div>
            <x-filament::button
                type="button"
                icon="heroicon-o-plus-circle"
                wire:click="crearSesion"
                wire:loading.attr="disabled"
            >
                Crear sesión y obtener QR
            </x-filament::button>
        </div>

        {{-- Lista de sesiones --}}
        @if(count($sesiones) > 0)
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Nombre</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">ID</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Estado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">Teléfono</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @foreach($sesiones as $sesion)
                            @php
                                $id       = $sesion['id'] ?? '—';
                                $nombre   = $sesion['name'] ?? $sesion['id'] ?? '—';
                                $status   = $sesion['status'] ?? 'unknown';
                                $telefono = $sesion['phone'] ?? '';
                                $esActiva = $id === setting('owa_session_id', config('services.openwa.session'));

                                $colorStatus = match($status) {
                                    'WORKING', 'ready' => 'text-green-600 dark:text-green-400',
                                    'STARTING', 'starting', 'qr' => 'text-yellow-600 dark:text-yellow-400',
                                    default => 'text-red-500 dark:text-red-400',
                                };
                            @endphp
                            <tr class="{{ $esActiva ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $nombre }}
                                    @if($esActiva)
                                        <span class="ml-2 inline-flex items-center rounded-full bg-green-100 dark:bg-green-900 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">
                                            activa
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ Str::limit($id, 20) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium {{ $colorStatus }}">{{ $status }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $telefono ?: '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        {{-- Sesión activa: solo mostrar logout --}}
                                        @if($esActiva)
                                            <span class="inline-flex items-center text-xs text-gray-400 dark:text-gray-500 italic mr-2">Sesión en uso</span>
                                            @if($status === 'ready' || $status === 'WORKING')
                                                <x-filament::button
                                                    size="xs"
                                                    color="danger"
                                                    icon="heroicon-o-arrow-right-on-rectangle"
                                                    wire:click="desconectarSesion('{{ $id }}')"
                                                    wire:confirm="¿Desconectar este número? El chatbot dejará de funcionar hasta que vincules otro."
                                                >
                                                    Desconectar
                                                </x-filament::button>
                                            @endif
                                        @else
                                            {{-- Sesiones no activas --}}
                                            @if(! in_array($status, ['ready', 'WORKING']))
                                                <x-filament::button
                                                    size="xs"
                                                    color="warning"
                                                    icon="heroicon-o-qr-code"
                                                    wire:click="obtenerQr('{{ $id }}')"
                                                >
                                                    Ver QR
                                                </x-filament::button>
                                            @endif

                                            <x-filament::button
                                                size="xs"
                                                color="success"
                                                icon="heroicon-o-check-circle"
                                                wire:click="usarSesion('{{ $id }}', '{{ $telefono }}')"
                                            >
                                                Usar
                                            </x-filament::button>

                                            <x-filament::button
                                                size="xs"
                                                color="danger"
                                                icon="heroicon-o-trash"
                                                wire:click="eliminarSesion('{{ $id }}')"
                                                wire:confirm="¿Eliminar esta sesión? Se desconectará el teléfono asociado."
                                            >
                                                Eliminar
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center text-gray-400 dark:text-gray-500">
                No hay sesiones en OpenWA o no se pudo conectar con la API.
            </div>
        @endif

        {{-- QR Code --}}
        @if($qrCodeData)
            <div class="rounded-xl border border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-6 text-center space-y-4">
                <p class="font-semibold text-yellow-800 dark:text-yellow-200 text-base">
                    📱 Escanea este QR con WhatsApp → Dispositivos vinculados → Vincular dispositivo
                </p>
                <div class="flex justify-center">
                    @if(str_starts_with($qrCodeData, 'data:image'))
                        <img src="{{ $qrCodeData }}" alt="QR WhatsApp" class="w-64 h-64 rounded-lg border border-gray-200 dark:border-gray-600" />
                    @else
                        {{-- Si es string raw del QR, usar Google Charts como fallback --}}
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=256x256&data={{ urlencode($qrCodeData) }}"
                            alt="QR WhatsApp"
                            class="w-64 h-64 rounded-lg border border-gray-200 dark:border-gray-600"
                        />
                    @endif
                </div>
                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                    El QR expira en ~20 segundos. Si ya no funciona, presiona "Ver QR" de nuevo.
                </p>
                <div class="flex justify-center gap-3">
                    <x-filament::button
                        type="button"
                        color="warning"
                        icon="heroicon-o-arrow-path"
                        wire:click="obtenerQr('{{ $qrSesionId }}')"
                    >
                        Refrescar QR
                    </x-filament::button>
                    <x-filament::button
                        type="button"
                        color="gray"
                        wire:click="$set('qrCodeData', null)"
                    >
                        Cerrar
                    </x-filament::button>
                </div>
            </div>
        @endif
    </div>

</x-filament-panels::page>
