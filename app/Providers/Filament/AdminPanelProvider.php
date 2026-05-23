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
            ->homeUrl(fn () => auth()->user()?->hasRole('asesor')
                ? '/admin/dashboard-asesor'
                : '/admin'
            )
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
                  ->brandLogoHeight('2.5rem');
        }

        if ($favicon) {
            $panel->favicon(asset('storage/' . $favicon));
        }

        $panel->renderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                Blade::render(
                    '<div class="text-center mb-4 leading-tight">
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
                    '<p class="text-center text-xs mt-6" style="color: #6b7280;">v{{ $version }}</p>',
                    ['version' => config('app.version', '1.0.0')]
                )
            )
        );

        $panel->renderHook(
            PanelsRenderHook::BODY_END,
            fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                Blade::render('<x-env-ribbon />')
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
