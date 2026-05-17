<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Tarjetas de stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($this->getStats() as $stat)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center gap-4">
                <div class="rounded-full p-3
                    @if($stat['color'] === 'success') bg-green-100 text-green-600
                    @elseif($stat['color'] === 'warning') bg-yellow-100 text-yellow-600
                    @elseif($stat['color'] === 'danger')  bg-red-100 text-red-600
                    @elseif($stat['color'] === 'info')    bg-blue-100 text-blue-600
                    @else bg-gray-100 text-gray-500
                    @endif">
                    <x-filament::icon :icon="$stat['icono']" class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $stat['valor'] }}</p>
                    <p class="text-xs font-medium text-gray-600">{{ $stat['label'] }}</p>
                    <p class="text-xs text-gray-400">{{ $stat['descripcion'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Tokens activos --}}
        <x-filament::section>
            <x-slot name="heading">Tokens Sanctum activos</x-slot>
            <x-slot name="description">Sesiones activas de la app móvil por asesor</x-slot>

            @php $tokens = $this->getTokensActivos() @endphp

            @if($tokens->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <x-filament::icon icon="heroicon-o-key" class="w-10 h-10 mx-auto mb-2 opacity-40" />
                    <p class="text-sm">No hay tokens activos.</p>
                    <p class="text-xs mt-1">Se generarán cuando los asesores inicien sesión desde la app.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs text-gray-500 uppercase">
                                <th class="py-2 pr-4">Asesor</th>
                                <th class="py-2 pr-4">Nombre del token</th>
                                <th class="py-2 pr-4">Creado</th>
                                <th class="py-2 pr-4">Último uso</th>
                                <th class="py-2">Expira</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($tokens as $token)
                            <tr>
                                <td class="py-2 pr-4">
                                    <p class="font-medium text-gray-800">{{ $token->usuario }}</p>
                                    <p class="text-xs text-gray-400">{{ $token->email }}</p>
                                </td>
                                <td class="py-2 pr-4">
                                    <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ $token->token_name }}</code>
                                </td>
                                <td class="py-2 pr-4 text-xs text-gray-500">{{ $token->created_at }}</td>
                                <td class="py-2 pr-4 text-xs text-gray-500">{{ $token->last_used_at ?? '—' }}</td>
                                <td class="py-2 text-xs">
                                    @if($token->expires_at)
                                        <span class="{{ \Carbon\Carbon::parse($token->expires_at)->isPast() ? 'text-red-500' : 'text-green-600' }}">
                                            {{ $token->expires_at }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Sin expiración</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Dispositivos FCM --}}
        <x-filament::section>
            <x-slot name="heading">Dispositivos registrados (Push notifications)</x-slot>
            <x-slot name="description">Dispositivos con token FCM para recibir notificaciones</x-slot>

            @php $dispositivos = $this->getDispositivosRegistrados() @endphp

            @if($dispositivos->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <x-filament::icon icon="heroicon-o-device-phone-mobile" class="w-10 h-10 mx-auto mb-2 opacity-40" />
                    <p class="text-sm">No hay dispositivos registrados aún.</p>
                    <p class="text-xs mt-1">La tabla <code>device_tokens</code> se creará cuando se implemente la API.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs text-gray-500 uppercase">
                                <th class="py-2 pr-4">Asesor</th>
                                <th class="py-2 pr-4">Plataforma</th>
                                <th class="py-2">Actualizado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($dispositivos as $d)
                            <tr>
                                <td class="py-2 pr-4">
                                    <p class="font-medium">{{ $d->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $d->email }}</p>
                                </td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full
                                        {{ $d->plataforma === 'ios' ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-700' }}">
                                        {{ strtoupper($d->plataforma) }}
                                    </span>
                                </td>
                                <td class="py-2 text-xs text-gray-500">{{ $d->updated_at }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>

        {{-- Estado sync offline --}}
        <x-filament::section>
            <x-slot name="heading">Sincronización offline</x-slot>
            <x-slot name="description">Estado de la cola de operaciones pendientes</x-slot>

            @php $sync = $this->getSyncPendientes() @endphp

            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-3xl font-bold text-yellow-600">{{ $sync['total'] }}</p>
                    <p class="text-xs text-yellow-700 mt-1">Pendientes de sync</p>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-3xl font-bold text-red-600">{{ $sync['errores'] }}</p>
                    <p class="text-xs text-red-700 mt-1">Con error</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-bold text-gray-700">{{ $sync['ultima_sync'] ?? '—' }}</p>
                    <p class="text-xs text-gray-500 mt-1">Última sincronización</p>
                </div>
            </div>

            @if($sync['total'] === 0 && $sync['errores'] === 0)
            <p class="text-center text-xs text-gray-400 mt-4">
                La tabla <code>sync_queue</code> se creará cuando se implemente la API móvil.
            </p>
            @endif
        </x-filament::section>

    </div>
</x-filament-panels::page>
