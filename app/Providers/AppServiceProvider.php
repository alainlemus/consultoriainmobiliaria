<?php

namespace App\Providers;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Observers\ComisionObserver;
use App\Observers\ContactoObserver;
use App\Observers\ExpedienteObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        }

        Expediente::observe(ExpedienteObserver::class);
        Comision::observe(ComisionObserver::class);
        Contacto::observe(ContactoObserver::class);
    }
}
