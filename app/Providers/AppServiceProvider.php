<?php

namespace App\Providers;

use App\Models\Expediente;
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
    }
}
