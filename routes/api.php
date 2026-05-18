<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContactoController;
use App\Http\Controllers\Api\V1\ExpedienteController;
use App\Http\Controllers\Api\V1\DocumentoController;
use App\Http\Controllers\Api\V1\UbicacionController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\SyncController;

/*
|--------------------------------------------------------------------------
| API Routes — versión 1
|--------------------------------------------------------------------------
| Prefijo automático: /api  (definido en bootstrap/app.php)
| Autenticación:      Laravel Sanctum (Bearer token)
*/

// ── Descarga de documento con URL firmada temporalmente ────────────────────
// No requiere Bearer token — la firma de la URL es la autorización.
// Válida 5 minutos (generada por DocumentoController@ver).
Route::get(
    '/documentos/{expedienteId}/{documentoId}/descargar',
    [DocumentoController::class, 'descargar']
)->middleware('signed')->name('api.documentos.descargar');

Route::prefix('v1')->group(function () {

    // ── Públicas (sin token) ──────────────────────────────────────────────
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ── Protegidas (requieren Bearer token Sanctum) ───────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me',      [AuthController::class, 'me']);

        // Prospectos / Contactos
        Route::get('/contactos',          [ContactoController::class, 'index']);
        Route::post('/contactos',         [ContactoController::class, 'store']);
        Route::get('/contactos/{id}',     [ContactoController::class, 'show']);
        Route::put('/contactos/{id}',     [ContactoController::class, 'update']);

        // Expedientes
        Route::get('/expedientes',        [ExpedienteController::class, 'index']);
        Route::post('/expedientes',       [ExpedienteController::class, 'store']);
        Route::get('/expedientes/{id}',   [ExpedienteController::class, 'show']);
        Route::put('/expedientes/{id}',   [ExpedienteController::class, 'update']);

        // Documentos
        Route::get('/expedientes/{expedienteId}/documentos',                                   [DocumentoController::class, 'index']);
        Route::post('/expedientes/{expedienteId}/documentos',                                  [DocumentoController::class, 'store']);
        Route::get('/expedientes/{expedienteId}/documentos/{documentoId}/ver',                 [DocumentoController::class, 'ver']);
        Route::delete('/expedientes/{expedienteId}/documentos/{documentoId}',                  [DocumentoController::class, 'destroy']);
        Route::post('/expedientes/{expedienteId}/documentos/{documentoId}/reemplazar',         [DocumentoController::class, 'reemplazar']);

        // Ubicaciones GPS
        Route::post('/ubicaciones',       [UbicacionController::class, 'store']);
        Route::get('/ubicaciones/mapa',   [UbicacionController::class, 'mapa']);

        // Sync offline batch
        Route::post('/sync',              [SyncController::class, 'batch']);

        // Dispositivos FCM
        Route::post('/dispositivos',      [DeviceTokenController::class, 'store']);
    });
});
