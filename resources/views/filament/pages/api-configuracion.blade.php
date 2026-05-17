<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Aviso de estado --}}
        <x-filament::section>
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-information-circle" class="w-6 h-6 text-blue-500" />
                <div>
                    <p class="font-semibold text-sm">La API aún no está implementada</p>
                    <p class="text-xs text-gray-500">
                        Esta sección muestra la configuración planificada para la app móvil de asesores.
                        Los endpoints se habilitarán en la Fase 1 del desarrollo.
                    </p>
                </div>
            </div>
        </x-filament::section>

        <form wire:submit="calcular">
            {{ $this->form }}
        </form>

        {{-- Acciones --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-sm">Revocar todos los tokens</p>
                    <p class="text-xs text-gray-500">Cierra sesión de todos los asesores en la app móvil.</p>
                </div>
                <x-filament::button
                    wire:click="revocarTodosLosTokens"
                    color="danger"
                    icon="heroicon-o-x-circle"
                    wire:confirm="¿Seguro que deseas revocar todos los tokens? Los asesores deberán volver a iniciar sesión."
                >
                    Revocar todos los tokens
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
