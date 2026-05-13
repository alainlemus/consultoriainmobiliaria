<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-check">
                Guardar configuración SEO
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
