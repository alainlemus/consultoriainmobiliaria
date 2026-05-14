<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\PropiedadController;

use App\Http\Controllers\KpisReporteController;
use App\Http\Controllers\ContratosController;
use App\Http\Controllers\TestimonioPublicoController;

// Página principal
Route::get('/', [HomeController::class, 'index'])->name('home');

// Blog / ConsulTips
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Contacto (POST)
Route::post('/contacto', [ContactoController::class, 'store'])->name('contacto.store');

// Propiedades
Route::get('/propiedades', [PropiedadController::class, 'index'])->name('propiedades.index');
Route::get('/propiedades/{slug}', [PropiedadController::class, 'show'])->name('propiedades.show');

// Aviso de privacidad
Route::view('/aviso-de-privacidad', 'pages.aviso-privacidad')->name('aviso.privacidad');

// Reportes KPIs (solo super_admin — protegido en el controller)
Route::middleware(['web', 'auth'])->prefix('admin/reportes/kpis')->name('kpis.reporte.')->group(function () {
    Route::get('/excel', [KpisReporteController::class, 'excel'])->name('excel');
    Route::get('/pdf',   [KpisReporteController::class, 'pdf'])->name('pdf');
});

// Contratos PDF por expediente
Route::middleware(['web', 'auth'])->prefix('admin/contratos')->name('contratos.')->group(function () {
    Route::get('/{expediente}/prestacion-servicios',[ContratosController::class, 'prestacionServicios'])->name('prestacion_servicios');
    Route::get('/{expediente}/convenio-honorarios', [ContratosController::class, 'convenioHonorarios'])->name('convenio_honorarios');
});

// Formulario de testimonio con token de un solo uso (7 días de vigencia)
// Solo accesible con el link único enviado por email al acreditado
Route::get('/testimonio/gracias',     [TestimonioPublicoController::class, 'gracias'])->name('testimonio.gracias');
Route::get('/testimonio/{token}',     [TestimonioPublicoController::class, 'show'])->name('testimonio.show');
Route::post('/testimonio/{token}',    [TestimonioPublicoController::class, 'store'])->name('testimonio.store');
