<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\PropiedadController;

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
