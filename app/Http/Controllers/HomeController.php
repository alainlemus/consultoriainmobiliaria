<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Testimonio;
use App\Models\Servicio;
use App\Models\Proceso;
use App\Models\Cobertura;
use App\Models\Propiedad;
use App\Http\Controllers\ContactoController;

class HomeController extends Controller
{
    public function index()
    {
        $posts       = Post::published()->latest('published_at')->take(3)->get();
        $testimonios = Testimonio::activos()->get();
        $captcha     = ContactoController::generarCaptcha();
        $servicios   = Servicio::activos()->get();
        $procesos    = Proceso::activos()->get();
        $coberturas  = Cobertura::activos()->get();
        $propiedades = Propiedad::where('destacada', true)->where('estatus', 'en_venta')->latest()->take(4)->get();

        return view('pages.home', compact(
            'posts', 'testimonios', 'captcha',
            'servicios', 'procesos', 'coberturas', 'propiedades'
        ));
    }
}
