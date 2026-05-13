<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Models\Configuracion;

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
                \App\Filament\Widgets\CrmStatsOverview::class,
                \App\Filament\Widgets\ExpedientesAnualesChart::class,
                \App\Filament\Widgets\KpisMensualesWidget::class,
                \App\Filament\Widgets\FunnelEtapasWidget::class,
                \App\Filament\Widgets\ExpedientesSinMovimientoWidget::class,
                \App\Filament\Widgets\RankingAsesoresWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
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

        return $panel;
    }
}
