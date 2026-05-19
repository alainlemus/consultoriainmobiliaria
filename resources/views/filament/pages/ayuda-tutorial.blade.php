<x-filament-panels::page>
    @if($this->getRol() === 'super_admin')
        <div x-data="{ vista: 'admin' }" class="space-y-4">

            {{-- Selector de guía --}}
            <div class="flex gap-2 p-1 bg-gray-100 dark:bg-white/5 rounded-xl w-fit">
                <button
                    @click="vista = 'admin'"
                    :class="vista === 'admin'
                        ? 'bg-white dark:bg-white/10 shadow text-gray-900 dark:text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                >
                    Guía Administrador
                </button>
                <button
                    @click="vista = 'asesor'"
                    :class="vista === 'asesor'
                        ? 'bg-white dark:bg-white/10 shadow text-gray-900 dark:text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                >
                    Guía Asesor
                </button>
            </div>

            <div x-show="vista === 'admin'" x-transition>
                {{ $this->adminInfolist }}
            </div>

            <div x-show="vista === 'asesor'" x-transition>
                {{ $this->asesorInfolist }}
            </div>
        </div>
    @else
        {{ $this->asesorInfolist }}
    @endif
</x-filament-panels::page>
