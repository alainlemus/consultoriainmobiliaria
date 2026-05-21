<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectAsesorToExpedientes;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Models\Configuracion;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $logo    = Configuracion::get('logo');
            $favicon = Configuracion::get('favicon');
        } catch (\Throwable) {
            $logo = $favicon = null;
        }

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->colors([
                'primary' => Color::hex('#B8960C'),
            ])
            ->brandName('Consultoría Inmobiliaria')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // pages([]) vacío para que discoverPages tome el control (incluye nuestro Dashboard)
            ->pages([])
            // Widgets registrados en el panel para que Livewire los conozca
            // El Dashboard decide cuáles mostrar vía getWidgets()
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\ProspectosPendientesCierreWidget::class,
                \App\Filament\Widgets\CrmStatsOverview::class,
                \App\Filament\Widgets\ExpedientesAnualesChart::class,
                \App\Filament\Widgets\KpisMensualesWidget::class,
                \App\Filament\Widgets\FunnelEtapasWidget::class,
                \App\Filament\Widgets\ExpedientesSinMovimientoWidget::class,
                \App\Filament\Widgets\RankingAsesoresWidget::class,
                \App\Filament\Widgets\AsesorStatsOverview::class,
                \App\Filament\Widgets\AsesorProspectosWidget::class,
                \App\Filament\Widgets\AsesorExpedientesWidget::class,
                \App\Filament\Widgets\AsesorComisionesWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->homeUrl(fn () => auth()->user()?->hasRole('asesor')
                ? '/admin/dashboard-asesor'
                : '/admin'
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                RedirectAsesorToExpedientes::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        if ($logo) {
            $panel->brandLogo(asset('storage/' . $logo))
                  ->brandLogoHeight('2.5rem');
        }

        if ($favicon) {
            $panel->favicon(asset('storage/' . $favicon));
        }

        $panel->renderHook(
            PanelsRenderHook::TOPBAR_END,
            fn (): string => Blade::render('
                @php
                    $user = filament()->auth()->user();
                    $rol  = $user?->roles->first()?->name;
                    $label = match($rol) {
                        "super_admin" => "Administrador",
                        "asesor"      => "Asesor",
                        default       => ucfirst($rol ?? ""),
                    };
                    $classes = $rol === "super_admin"
                        ? "bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200"
                        : "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200";
                @endphp
                @if($label)
                    <span class="hidden sm:inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $classes }}">
                        {{ $label }}
                    </span>
                @endif
            ')
        );

        return $panel;
    }
}
