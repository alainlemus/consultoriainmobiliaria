<?php

namespace App\Providers;

use App\Models\Expediente;
use App\Observers\ExpedienteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Expediente::observe(ExpedienteObserver::class);
    }
}
