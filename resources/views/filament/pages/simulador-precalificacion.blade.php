<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Formulario --}}
        <x-filament::section>
            <x-slot name="heading">Datos para calcular</x-slot>
            <x-slot name="description">Ingresa los datos del prospecto para obtener una estimación del crédito FOVISSSTE.</x-slot>

            <form wire:submit.prevent="calcular">
                {{ $this->form }}

                <div class="mt-6">
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-calculator">
                        Calcular precalificación
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Resultados --}}
        @if (!empty($resultado))
            @php $r = $resultado; @endphp

            <x-filament::section>
                <x-slot name="heading">
                    Resultado — {{ $r['tipo'] ?? '' }}
                </x-slot>

                {{-- Alerta de elegibilidad --}}
                @if (!($r['elegible'] ?? true))
                    <div class="rounded-lg bg-danger-50 border border-danger-200 p-4 mb-4 dark:bg-danger-900/20 dark:border-danger-700">
                        <p class="font-semibold text-danger-700 dark:text-danger-400 mb-1">El prospecto NO cumple los requisitos:</p>
                        <ul class="list-disc pl-5 text-sm text-danger-600 dark:text-danger-300 space-y-1">
                            @foreach ($r['errores'] as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="rounded-lg bg-success-50 border border-success-200 p-3 mb-4 dark:bg-success-900/20 dark:border-success-700">
                        <p class="font-semibold text-success-700 dark:text-success-400">✓ El prospecto cumple los requisitos básicos para este tipo de crédito.</p>
                    </div>
                @endif

                {{-- Tarjetas principales --}}
                @isset($r['precio_inmueble_deseado'])
                    @if ($r['alcanza'])
                        <div class="rounded-lg bg-success-50 border border-success-300 p-4 mb-4 dark:bg-success-900/20 dark:border-success-700 flex items-start gap-3">
                            <span class="text-2xl leading-none">✓</span>
                            <div>
                                <p class="font-semibold text-success-700 dark:text-success-400">
                                    El crédito <strong>alcanza</strong> para el inmueble deseado de {{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['precio_inmueble_deseado']) }}
                                </p>
                                <p class="text-sm text-success-600 dark:text-success-300 mt-0.5">
                                    Excedente disponible: <strong>{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['diferencia']) }}</strong>
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg bg-danger-50 border border-danger-300 p-4 mb-4 dark:bg-danger-900/20 dark:border-danger-700 flex items-start gap-3">
                            <span class="text-2xl leading-none">✗</span>
                            <div>
                                <p class="font-semibold text-danger-700 dark:text-danger-400">
                                    El crédito <strong>no alcanza</strong> para el inmueble deseado de {{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['precio_inmueble_deseado']) }}
                                </p>
                                <p class="text-sm text-danger-600 dark:text-danger-300 mt-0.5">
                                    Diferencia faltante: <strong>{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt(abs($r['diferencia'])) }}</strong>
                                </p>
                            </div>
                        </div>
                    @endif
                @endisset

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">                    {{-- Monto crédito --}}
                    <div class="rounded-xl bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 p-4 text-center">
                        <p class="text-xs font-medium text-primary-500 uppercase tracking-wide mb-1">Monto del crédito</p>
                        <p class="text-2xl font-bold text-primary-700 dark:text-primary-300">
                            {{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['monto_credito'] ?? 0) }}
                        </p>
                        <p class="text-xs text-primary-400 mt-1">MXN</p>
                    </div>

                    {{-- Valor inmueble --}}
                    <div class="rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 text-center">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Valor del inmueble</p>
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                            {{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['valor_inmueble'] ?? 0) }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">crédito + subcuenta</p>
                    </div>

                    {{-- Mensualidad --}}
                    <div class="rounded-xl bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 p-4 text-center">
                        <p class="text-xs font-medium text-warning-500 uppercase tracking-wide mb-1">Mensualidad estimada</p>
                        <p class="text-2xl font-bold text-warning-700 dark:text-warning-300">
                            {{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['mensualidad'] ?? 0) }}
                        </p>
                        <p class="text-xs text-warning-400 mt-1">{{ $r['plazo_años'] ?? 0 }} años · {{ $r['tasa_anual'] ?? 0 }}% anual</p>
                    </div>

                    {{-- Honorarios estimados --}}
                    <div class="rounded-xl bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-700 p-4 text-center">
                        <p class="text-xs font-medium text-success-500 uppercase tracking-wide mb-1">Honorarios estimados (8%)</p>
                        <p class="text-2xl font-bold text-success-700 dark:text-success-300">
                            {{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['honorarios'] ?? 0) }}
                        </p>
                        <p class="text-xs text-success-400 mt-1">orientativo</p>
                    </div>
                </div>

                {{-- Detalle del cálculo --}}
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="text-left px-4 py-2 text-gray-500 font-medium">Concepto</th>
                                <th class="text-right px-4 py-2 text-gray-500 font-medium">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">Monto del crédito FOVISSSTE</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-white">{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['monto_credito'] ?? 0) }}</td>
                            </tr>
                            @if (($r['subcuenta'] ?? 0) > 0)
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">+ Subcuenta de vivienda</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-white">{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['subcuenta'] ?? 0) }}</td>
                            </tr>
                            @endif
                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                <td class="px-4 py-2 font-semibold text-gray-700 dark:text-gray-300">= Valor total del inmueble</td>
                                <td class="px-4 py-2 text-right font-bold text-primary-600 dark:text-primary-400">{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['valor_inmueble'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">Tasa de interés anual</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ $r['tasa_anual'] ?? 0 }}%</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">Plazo</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ $r['plazo_años'] ?? 0 }} años ({{ ($r['plazo_años'] ?? 0) * 12 }} mensualidades)</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">Mensualidad estimada</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-white">{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['mensualidad'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">Capacidad de pago máxima (30% sueldo)</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-white">{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['pago_max_mensual'] ?? 0) }}</td>
                            </tr>
                            <tr class="bg-success-50 dark:bg-success-900/10">
                                <td class="px-4 py-2 text-success-700 dark:text-success-300">Honorarios estimados (8%)</td>
                                <td class="px-4 py-2 text-right font-semibold text-success-700 dark:text-success-300">{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['honorarios'] ?? 0) }}</td>
                            </tr>
                            @isset($r['precio_inmueble_deseado'])
                            <tr class="border-t-2 border-gray-300 dark:border-gray-500 bg-gray-100 dark:bg-gray-700">
                                <td class="px-4 py-2 font-semibold text-gray-800 dark:text-gray-100">Precio del inmueble deseado</td>
                                <td class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white">{{ \App\Filament\Pages\SimuladorPrecalificacion::fmt($r['precio_inmueble_deseado']) }}</td>
                            </tr>
                            <tr class="{{ $r['alcanza'] ? 'bg-green-100 dark:bg-green-900/40' : 'bg-red-100 dark:bg-red-900/40' }}">
                                <td class="px-4 py-2 font-bold {{ $r['alcanza'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    {{ $r['alcanza'] ? '✓ Excedente disponible' : '✗ Diferencia faltante' }}
                                </td>
                                <td class="px-4 py-2 text-right font-bold {{ $r['alcanza'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    {{ \App\Filament\Pages\SimuladorPrecalificacion::fmt(abs($r['diferencia'])) }}
                                </td>
                            </tr>
                            @endisset
                        </tbody>
                    </table>
                </div>

                {{-- Notas del cálculo --}}
                @if (!empty($r['notas']))
                    <div class="mt-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Notas del cálculo</p>
                        <ul class="space-y-1">
                            @foreach ($r['notas'] as $nota)
                                <li class="text-sm text-gray-600 dark:text-gray-400 flex gap-2">
                                    <span class="text-gray-400">·</span> {{ $nota }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Aviso legal --}}
                <p class="mt-4 text-xs text-gray-400 dark:text-gray-500 italic">
                    * Los resultados son una estimación orientativa basada en UMA mensual ${{ number_format(\App\Services\UmaService::getUmaMensual(), 2) }} ({{ \App\Services\UmaService::getVigencia() }}).
                    El monto definitivo lo determina el portal oficial de FOVISSSTE con la CURP del acreditado.
                </p>

                {{-- Acceso rápido a simuladores oficiales --}}
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="https://inscripcioncontinua.fovissste.gob.mx/simulador/" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-700 hover:bg-green-800 transition-colors shadow-sm">
                        Verificar en simulador oficial FOVISSSTE →
                    </a>
                    <a href="https://micuenta.infonavit.org.mx/" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-red-700 hover:bg-red-800 transition-colors shadow-sm">
                        Portal INFONAVIT →
                    </a>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
