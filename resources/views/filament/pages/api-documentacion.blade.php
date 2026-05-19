<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Aviso --}}
        <x-filament::section>
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-book-open" class="w-6 h-6 text-indigo-500 mt-0.5 shrink-0" />
                <div>
                    <p class="font-semibold text-sm">Referencia completa de la API REST v1</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Todos los endpoints requieren el header
                        <code class="bg-gray-100 px-1 rounded text-xs">Authorization: Bearer {token}</code>
                        excepto <code class="bg-gray-100 px-1 rounded text-xs">POST /auth/login</code>.
                        URL base: <strong>{{ config('app.url') }}/api/v1</strong>
                    </p>
                </div>
            </div>
        </x-filament::section>

        @foreach($this->getEndpoints() as $grupo => $endpoints)
        <x-filament::section>
            <x-slot name="heading">{{ $grupo }}</x-slot>

            <div class="space-y-4">
                @foreach($endpoints as $ep)
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    {{-- Cabecera del endpoint --}}
                    <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold font-mono {{ $this->getMethodColor($ep['method']) }}">
                            {{ $ep['method'] }}
                        </span>
                        <code class="text-sm font-mono text-gray-700 break-all">{{ $ep['ruta'] }}</code>
                    </div>
                    {{-- Descripción y ejemplos --}}
                    <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div>
                            <p class="font-semibold text-gray-500 uppercase tracking-wide mb-1">Descripción</p>
                            <p class="text-gray-700">{{ $ep['descripcion'] }}</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-500 uppercase tracking-wide mb-1">Body / Parámetros</p>
                            <pre class="bg-gray-900 text-green-400 rounded p-2 overflow-x-auto whitespace-pre-wrap text-xs leading-relaxed">{{ $ep['body'] }}</pre>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-500 uppercase tracking-wide mb-1">Respuesta</p>
                            <pre class="bg-gray-900 text-blue-300 rounded p-2 overflow-x-auto whitespace-pre-wrap text-xs leading-relaxed">{{ $ep['respuesta'] }}</pre>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-filament::section>
        @endforeach

        {{-- Códigos de error estándar --}}
        <x-filament::section>
            <x-slot name="heading">Códigos de respuesta HTTP</x-slot>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                @foreach([
                    ['200', 'OK — operación exitosa', 'text-green-700 bg-green-50'],
                    ['201', 'Created — recurso creado', 'text-green-700 bg-green-50'],
                    ['400', 'Bad Request — datos inválidos', 'text-yellow-700 bg-yellow-50'],
                    ['401', 'Unauthorized — token inválido o expirado', 'text-red-700 bg-red-50'],
                    ['403', 'Forbidden — sin permiso', 'text-red-700 bg-red-50'],
                    ['404', 'Not Found — recurso no existe', 'text-red-700 bg-red-50'],
                    ['422', 'Unprocessable — validación fallida', 'text-yellow-700 bg-yellow-50'],
                    ['500', 'Server Error — error interno', 'text-red-700 bg-red-50'],
                ] as [$code, $desc, $color])
                <div class="rounded-lg p-3 {{ $color }}">
                    <p class="font-bold font-mono text-lg">{{ $code }}</p>
                    <p class="mt-0.5">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
