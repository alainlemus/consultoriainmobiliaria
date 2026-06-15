<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContactoController;
use App\Http\Controllers\Api\V1\ExpedienteController;
use App\Http\Controllers\Api\V1\DocumentoController;
use App\Http\Controllers\Api\V1\UbicacionController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\ComisionController;
use App\Http\Controllers\Api\WhatsAppWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes — versión 1
|--------------------------------------------------------------------------
| Prefijo automático: /api  (definido en bootstrap/app.php)
| Autenticación:      Laravel Sanctum (Bearer token)
*/

// ── Webhook de OpenWA (sin auth, verificación por secret) ─────────────────
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.whatsapp');

// ── Descarga de documento con URL firmada temporalmente ────────────────────
// No requiere Bearer token — la firma de la URL es la autorización.
// Válida 5 minutos (generada por DocumentoController@ver).
Route::get(
    '/documentos/{expedienteId}/{documentoId}/descargar',
    [DocumentoController::class, 'descargar']
)->middleware('signed')->name('api.documentos.descargar');

// ── Foto de visita con URL firmada (1 hora) ────────────────────────────────
Route::get(
    '/ubicaciones/fotos/{fotoId}',
    [UbicacionController::class, 'verFoto']
)->middleware('signed')->name('api.ubicacion.foto');

// ── Foto de perfil de usuario con URL firmada (1 hora) ─────────────────────
Route::get(
    '/users/{user}/foto-perfil',
    [AuthController::class, 'verFotoPerfil']
)->middleware('signed')->name('api.user.foto');

Route::prefix('v1')->group(function () {

    // ── Públicas (sin token) ──────────────────────────────────────────────
    Route::post('/auth/login',           [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

    // ── Protegidas (requieren Bearer token Sanctum) ───────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout',           [AuthController::class, 'logout']);
        Route::get('/auth/me',                [AuthController::class, 'me']);
        Route::put('/auth/perfil',            [AuthController::class, 'updatePerfil']);
        Route::post('/auth/perfil/foto',      [AuthController::class, 'subirFotoPerfil']);

        // Prospectos / Contactos
        Route::get('/contactos',                              [ContactoController::class, 'index']);
        Route::post('/contactos',                             [ContactoController::class, 'store']);
        Route::get('/contactos/{id}',                        [ContactoController::class, 'show']);
        Route::put('/contactos/{id}',                        [ContactoController::class, 'update']);
        Route::post('/contactos/{id}/foto',                  [ContactoController::class, 'uploadFoto']);
        Route::post('/contactos/{id}/simulador-screenshot',  [ContactoController::class, 'uploadSimuladorScreenshot']);

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
        Route::post('/ubicaciones',                    [UbicacionController::class, 'store']);
        Route::get('/ubicaciones/mapa',                [UbicacionController::class, 'mapa']);
        Route::post('/ubicaciones/{id}/fotos',         [UbicacionController::class, 'subirFotos']);
        Route::patch('/ubicaciones/{id}/semaforo',     [UbicacionController::class, 'actualizarSemaforo']);

        // Escuelas — buscador para vincular prospectos
        Route::get('/escuelas',                        [UbicacionController::class, 'escuelas']);

        // Sync offline batch
        Route::post('/sync',              [SyncController::class, 'batch']);

        // Dispositivos FCM
        Route::post('/dispositivos',      [DeviceTokenController::class, 'store']);

        // Comisiones del asesor
        Route::get('/comisiones',         [ComisionController::class, 'index']);
        Route::get('/comisiones/resumen', [ComisionController::class, 'resumen']);
    });
});
