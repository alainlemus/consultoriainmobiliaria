<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ContactoController;
use App\Http\Controllers\Api\V1\ExpedienteController;
use App\Http\Controllers\Api\V1\DocumentoController;
use App\Http\Controllers\Api\V1\UbicacionController;
use App\Http\Controllers\Api\V1\AnuncioController;
use App\Http\Controllers\Api\V1\RouteController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\ComisionController;
use App\Http\Controllers\Api\V1\ContratosController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\V1\Acreditado\AuthController as AcreditadoAuthController;
use App\Http\Controllers\Api\V1\Acreditado\ExpedienteController as AcreditadoExpedienteController;
use App\Http\Controllers\Api\V1\Acreditado\DocumentoController as AcreditadoDocumentoController;
use App\Http\Controllers\Api\V1\Acreditado\SolicitudController as AcreditadoSolicitudController;

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

// ── Foto de anuncio con URL firmada (1 hora) ───────────────────────────────
Route::get(
    '/anuncios/fotos/{fotoId}',
    [AnuncioController::class, 'verFoto']
)->middleware('signed')->name('api.anuncio.foto');
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
        Route::post('/auth/logout',                [AuthController::class, 'logout']);
        Route::get('/auth/me',                     [AuthController::class, 'me']);
        Route::put('/auth/perfil',                 [AuthController::class, 'updatePerfil']);
        Route::post('/auth/perfil/foto',           [AuthController::class, 'subirFotoPerfil']);
        Route::get('/auth/asesores',               [AuthController::class, 'asesores']);
        Route::post('/auth/solicitar-cancelacion', [AuthController::class, 'solicitarCancelacion']);

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

        // Contratos — texto configurable para generar el PDF en la app (sin conexión)
        Route::get('/contratos/prestacion-servicios/config', [ContratosController::class, 'prestacionServiciosConfig']);
        Route::put('/contratos/prestacion-servicios/config', [ContratosController::class, 'updatePrestacionServiciosConfig']);

        // Documentos
        Route::get('/expedientes/{expedienteId}/documentos',                                   [DocumentoController::class, 'index']);
        Route::post('/expedientes/{expedienteId}/documentos',                                  [DocumentoController::class, 'store']);
        Route::get('/expedientes/{expedienteId}/documentos/{documentoId}/ver',                 [DocumentoController::class, 'ver']);
        Route::delete('/expedientes/{expedienteId}/documentos/{documentoId}',                  [DocumentoController::class, 'destroy']);
        Route::post('/expedientes/{expedienteId}/documentos/{documentoId}/reemplazar',         [DocumentoController::class, 'reemplazar']);
        Route::post('/expedientes/{expedienteId}/documentos/{documentoId}/rechazar',           [DocumentoController::class, 'rechazar']);

        // Ubicaciones GPS
        Route::post('/ubicaciones',                    [UbicacionController::class, 'store']);
        Route::get('/ubicaciones/mapa',                [UbicacionController::class, 'mapa']);
        Route::post('/ubicaciones/{id}/fotos',         [UbicacionController::class, 'subirFotos']);
        Route::patch('/ubicaciones/{id}/semaforo',     [UbicacionController::class, 'actualizarSemaforo']);

        // Escuelas — buscador para vincular prospectos
        Route::get('/escuelas',                        [UbicacionController::class, 'escuelas']);

        // Anuncios publicitarios
        Route::get('/anuncios/mapa',               [AnuncioController::class, 'mapa']);
        Route::post('/anuncios',                   [AnuncioController::class, 'store']);
        Route::patch('/anuncios/{id}/estado',      [AnuncioController::class, 'actualizarEstado']);
        Route::post('/anuncios/{id}/fotos',        [AnuncioController::class, 'subirFotos']);

        // Rutas de asesores (GPS tracking)
        Route::post('/routes/points',      [RouteController::class, 'storePoints']);
        Route::get('/routes/asesores',    [RouteController::class, 'listAsesores']);
        Route::get('/routes/points',       [RouteController::class, 'getPoints']);
        Route::get('/routes/dias',        [RouteController::class, 'getDias']);

        // Sync offline batch
        Route::post('/sync',              [SyncController::class, 'batch']);

        // Dispositivos FCM
        Route::post('/dispositivos',      [DeviceTokenController::class, 'store']);

        // Comisiones del asesor
        Route::get('/comisiones',         [ComisionController::class, 'index']);
        Route::get('/comisiones/resumen', [ComisionController::class, 'resumen']);
    });

    // ── Rutas para CRM (autenticación por sesión web, no token) ──────────────
    Route::middleware('auth:web')->group(function () {
        Route::get('/crm/routes/asesores', [RouteController::class, 'listAsesores']);
        Route::get('/crm/routes/points',   [RouteController::class, 'getPoints']);
        Route::get('/crm/routes/dias',     [RouteController::class, 'getDias']);
    });
});

// ══════════════════════════════════════════════════════════════════════════════
// API ACREDITADO — /api/v1/acreditado/
// Rutas para la app del acreditado (guard separado)
// ══════════════════════════════════════════════════════════════════════════════
Route::prefix('v1/acreditado')->group(function () {

    // ── Foto de perfil pública (URL firmada) ──────────────────────────────
    Route::get('/foto-perfil/{acreditado}', [AcreditadoAuthController::class, 'verFotoPerfil'])
        ->middleware('signed')
        ->name('api.acreditado.foto-perfil');

    // ── Auth pública (sin token) ──────────────────────────────────────────
    Route::post('/auth/registro',        [AcreditadoAuthController::class, 'registro']);
    Route::post('/auth/login',           [AcreditadoAuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AcreditadoAuthController::class, 'forgotPassword']);

    // ── Servicios disponibles (público, para mostrar en onboarding) ───────
    Route::get('/servicios', [AcreditadoSolicitudController::class, 'servicios']);

    // ── Rutas protegidas con Sanctum (token del acreditado) ───────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout',             [AcreditadoAuthController::class, 'logout']);
        Route::get('/auth/me',                  [AcreditadoAuthController::class, 'me']);
        Route::put('/auth/perfil',              [AcreditadoAuthController::class, 'updatePerfil']);
        Route::post('/auth/perfil/foto',        [AcreditadoAuthController::class, 'subirFoto']);
        Route::put('/auth/password',            [AcreditadoAuthController::class, 'cambiarPassword']);
        Route::post('/auth/solicitar-cancelacion', [AcreditadoAuthController::class, 'solicitarCancelacion']);

        // Solicitudes de asesoría
        Route::post('/solicitudes', [AcreditadoSolicitudController::class, 'store']);

        // Expediente
        Route::get('/expediente',                    [AcreditadoExpedienteController::class, 'show']);
        Route::get('/expediente/seguimiento',        [AcreditadoExpedienteController::class, 'seguimiento']);
        Route::get('/expediente/documentos-pendientes', [AcreditadoExpedienteController::class, 'documentosPendientes']);

        // Documentos del expediente
        Route::get('/expediente/documentos',         [AcreditadoDocumentoController::class, 'index']);
        Route::post('/expediente/documentos',        [AcreditadoDocumentoController::class, 'store']);
        Route::get('/expediente/documentos/{id}/ver', [AcreditadoDocumentoController::class, 'ver']);

        // Dispositivo (push notifications) — mismo controlador que el asesor,
        // DeviceToken es polimórfico (ver DeviceTokenController::store)
        Route::post('/dispositivos', [DeviceTokenController::class, 'store']);
    });
});
