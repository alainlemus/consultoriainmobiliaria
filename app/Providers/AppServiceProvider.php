<?php

namespace App\Providers;

use App\Models\Anuncio;
use App\Models\AnuncioFoto;
use App\Models\Comision;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\Ubicacion;
use App\Models\UbicacionFoto;
use App\Observers\ComisionObserver;
use App\Observers\ContactoObserver;
use App\Observers\ExpedienteObserver;
use App\Observers\MapaVisitasCacheObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\AuthUiEnhancerLogin;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        Livewire::component(
            'diogo-g-pinto.auth-u-i-enhancer.pages.auth.auth-ui-enhancer-login',
            AuthUiEnhancerLogin::class
        );

        Expediente::observe(ExpedienteObserver::class);
        Comision::observe(ComisionObserver::class);
        Contacto::observe(ContactoObserver::class);

        Ubicacion::observe(MapaVisitasCacheObserver::class);
        UbicacionFoto::observe(MapaVisitasCacheObserver::class);
        Anuncio::observe(MapaVisitasCacheObserver::class);
        AnuncioFoto::observe(MapaVisitasCacheObserver::class);
    }
}
