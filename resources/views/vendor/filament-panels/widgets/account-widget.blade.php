@php
    $user = filament()->auth()->user();
    $rol = $user?->roles->first()?->name;
    $rolLabel = match ($rol) {
        'super_admin' => 'Administrador',
        'asesor' => 'Asesor',
        default => ucfirst($rol ?? ''),
    };
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            <x-filament-panels::avatar.user size="lg" :user="$user" />

            <div class="flex-1">
                <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ __('filament-panels::widgets/account-widget.welcome', ['app' => config('app.name')]) }}
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ filament()->getUserName($user) }}
                </p>

                @if ($rolLabel)
                    <p class="mt-0.5">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $rol === 'super_admin' ? 'bg-amber-500 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                            {{ $rolLabel }}
                        </span>
                    </p>
                @endif
            </div>

            <form action="{{ filament()->getLogoutUrl() }}" method="post" class="my-auto">
                @csrf

                <x-filament::button color="gray" icon="heroicon-m-arrow-left-on-rectangle"
                    icon-alias="panels::widgets.account.logout-button" labeled-from="sm" tag="button" type="submit">
                    {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
