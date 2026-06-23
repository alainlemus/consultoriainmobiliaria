<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\PropiedadController;
use App\Http\Controllers\KpisReporteController;
use App\Http\Controllers\ContratosController;
use App\Http\Controllers\TestimonioPublicoController;
use App\Http\Controllers\SitemapController;
use App\Models\UbicacionFoto;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\CargaMasivaController;
use App\Http\Controllers\ExpedienteZipController;

// Alias para que el middleware 'auth' de Laravel redirija al login de Filament
Route::redirect('/login', '/admin/login', 302)->name('login');

// Ruta requerida por Password::sendResetLink() — redirige al reset de Filament con el token
Route::get('/admin/password-reset/reset/{token}', function (string $token) {
    $email = request('email');
    return redirect("/admin/password-reset/reset?token={$token}&email={$email}");
})->name('password.reset');

// Página principal
Route::get('/', [HomeController::class, 'index'])->name('home');

// Sitemap XML
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

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

// Eliminación de datos (requerido por Google Play)
Route::view('/eliminacion-de-datos', 'pages.eliminacion-datos')->name('datos.eliminacion');

// Reportes KPIs (solo super_admin — protegido en el controller)
Route::middleware(['web', 'auth'])->prefix('admin/reportes/kpis')->name('kpis.reporte.')->group(function () {
    Route::get('/excel', [KpisReporteController::class, 'excel'])->name('excel');
    Route::get('/pdf',   [KpisReporteController::class, 'pdf'])->name('pdf');
});

// Contratos PDF por expediente
Route::middleware(['web', 'auth'])->prefix('admin/contratos')->name('contratos.')->group(function () {
    Route::get('/{expediente}/prestacion-servicios',[ContratosController::class, 'prestacionServicios'])->name('prestacion_servicios');
    Route::get('/{expediente}/convenio-honorarios', [ContratosController::class, 'convenioHonorarios'])->name('convenio_honorarios');
    Route::get('/{expediente}/carta-mandato',       [ContratosController::class, 'cartaMandato'])->name('carta_mandato');
});

// Formulario de testimonio con token de un solo uso (7 días de vigencia)
// Solo accesible con el link único enviado por email al acreditado
Route::get('/testimonio/gracias',     [TestimonioPublicoController::class, 'gracias'])->name('testimonio.gracias');
Route::get('/testimonio/{token}',     [TestimonioPublicoController::class, 'show'])->name('testimonio.show');
Route::post('/testimonio/{token}',    [TestimonioPublicoController::class, 'store'])->name('testimonio.store');

// Fotos de visitas — solo super_admin autenticado en Filament
Route::middleware(['web', 'auth'])->get('/admin/ubicaciones/fotos/{fotoId}', function (int $fotoId) {
    abort_unless(auth()->user()?->hasRole('super_admin'), 403);

    $foto = UbicacionFoto::findOrFail($fotoId);

    if (! Storage::disk('local')->exists($foto->ruta)) {
        abort(404);
    }

    return response()->file(
        Storage::disk('local')->path($foto->ruta),
        ['Content-Type' => $foto->mime ?? 'image/jpeg']
    );
})->name('admin.ubicacion.foto');

// Carga masiva de carpeta → crea expediente
Route::middleware(['web', 'auth'])
    ->post('/admin/expedientes/upload-carpeta', [CargaMasivaController::class, 'store'])
    ->name('filament.admin.resources.expedientes.crear-desde-carpeta.upload');

// Subida individual de archivo (paso 1 del proceso de carga masiva)
Route::middleware(['web', 'auth'])
    ->post('/admin/expedientes/upload-archivo', [CargaMasivaController::class, 'uploadArchivo'])
    ->name('filament.admin.resources.expedientes.crear-desde-carpeta.upload-archivo');

// Descargar carpeta completa del expediente como ZIP
Route::middleware(['web', 'auth'])
    ->get('/admin/expedientes/{expediente}/descargar-zip', [ExpedienteZipController::class, 'descargar'])
    ->name('expediente.descargar.zip');
