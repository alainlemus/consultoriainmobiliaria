<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectAsesorToExpedientes;
use Filament\Http\Middleware\Authenticate;
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
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use App\Models\Configuracion;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $logo        = Configuracion::get('logo');
            $favicon     = Configuracion::get('favicon');
            $loginImage  = Configuracion::get('login_image');
        } catch (\Throwable) {
            $logo = $favicon = $loginImage = null;
        }

        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->maxContentWidth(\Filament\Support\Enums\Width::Full)
            ->theme(asset('css/filament/admin/theme.css'))
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
                AuthUIEnhancerPlugin::make()
                    ->formPanelPosition('right')
                    ->formPanelWidth('45%')
                    ->formPanelBackgroundColor(Color::hex('#1a1a1a'), 950)
                    ->emptyPanelView('filament.auth.login-panel')
                    ->emptyPanelBackgroundColor(Color::hex('#0d0d0d'), 950)
                    ->showEmptyPanelOnMobile(false),
            ])
            ->homeUrl(function () {
                $user = auth()->user();
                if ($user?->hasRole('asesor')) {
                    return '/admin/dashboard-asesor';
                }
                if ($user?->hasRole('super_admin') || $user?->hasRole('admin')) {
                    return '/admin';
                }
                // Cualquier otro rol (capturista, etc.) aterriza en expedientes
                return '/admin/expedientes';
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
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
                ->brandName('Consultoría Inmobiliaria')
                ->brandLogoHeight('2.5rem');
        }

        if ($favicon) {
            $panel->favicon(asset('storage/' . $favicon));
        }

        $panel->renderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                Blade::render(
                    '<div class="mb-4 leading-tight text-center">
                        <span class="block text-xl font-semibold tracking-widest uppercase" style="color: #9ca3af;">Consultoría</span>
                        <span class="block text-xl font-bold tracking-wider uppercase" style="color: #B8960C;">Inmobiliaria</span>
                    </div>',
                    []
                )
            )
        );

        $panel->renderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                Blade::render(
                    '<p class="mt-6 text-xs text-center" style="color: #6b7280;">v{{ $version }}</p>',
                    ['version' => config('app.version', '1.0.0')]
                )
            )
        );

        $panel->renderHook(
            PanelsRenderHook::BODY_END,
            fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                Blade::render('<x-env-ribbon />') .
                // ── Sonido para notificaciones nuevas ──────────────────────
                '<script>
(function() {
    function playDing() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc  = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            // Tono suave La5 (880 Hz) con fade out
            osc.type = "sine";
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.25, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.6);
        } catch(e) {}
    }

    // Observar el badge de la campana de notificaciones
    // Usa MutationObserver en el contenedor del botón de notificaciones
    function initSoundObserver() {
        var lastCount = parseInt(localStorage.getItem("fi_notif_count") || "0");

        var observer = new MutationObserver(function() {
            // Buscar el badge de notificaciones no leídas
            var badge = document.querySelector(".fi-topbar-database-notifications-btn .fi-badge");
            var current = badge ? parseInt(badge.textContent.trim() || "0") : 0;

            if (current > lastCount) {
                playDing();
            }
            lastCount = current;
            localStorage.setItem("fi_notif_count", lastCount);
        });

        var btn = document.querySelector(".fi-topbar-database-notifications-btn");
        if (btn) {
            observer.observe(btn, { childList: true, subtree: true, characterData: true });
        } else {
            // Reintentar si el panel aún no cargó el botón
            setTimeout(initSoundObserver, 1500);
        }
    }

    // Iniciar cuando el DOM esté listo
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initSoundObserver);
    } else {
        initSoundObserver();
    }

    // Reiniciar observer tras navegación Livewire (SPA)
    document.addEventListener("livewire:navigated", function() {
        setTimeout(initSoundObserver, 500);
    });
})();
</script>'
            )
        );

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
