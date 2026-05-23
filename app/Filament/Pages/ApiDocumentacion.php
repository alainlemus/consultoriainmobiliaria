<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ApiDocumentacion extends Page
{
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Documentación';
    protected static ?string $title           = 'Documentación de la API Móvil';
    protected static string | \UnitEnum | null $navigationGroup = 'API Móvil';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $slug            = 'api-documentacion';

    protected string $view = 'filament.pages.api-documentacion';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    /**
     * Retorna la estructura de endpoints agrupados para la vista.
     */
    public function getEndpoints(): array
    {
        $base = config('app.url') . '/api/v1';

        return [
            'Autenticación' => [
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/auth/login',
                    'descripcion' => 'Iniciar sesión con email y contraseña. Devuelve token Sanctum.',
                    'body'        => '{ "email": "asesor@ejemplo.com", "password": "secret" }',
                    'respuesta'   => '{ "token": "...", "user": { "id": 1, "name": "...", "roles": ["asesor"] } }',
                ],
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/auth/logout',
                    'descripcion' => 'Cerrar sesión — revoca el token actual.',
                    'body'        => '— (requiere header: Authorization: Bearer {token})',
                    'respuesta'   => '{ "message": "Sesión cerrada correctamente." }',
                ],
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/auth/me',
                    'descripcion' => 'Obtener datos del usuario autenticado.',
                    'body'        => '— (requiere Bearer token)',
                    'respuesta'   => '{ "id": 1, "name": "...", "email": "...", "roles": [...] }',
                ],
            ],
            'Prospectos (Contactos)' => [
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/contactos',
                    'descripcion' => 'Listar prospectos del asesor autenticado. Soporta filtros: ?estado=nuevo&q=nombre',
                    'body'        => '—',
                    'respuesta'   => '{ "data": [...], "meta": { "total": 10, "page": 1 } }',
                ],
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/contactos',
                    'descripcion' => 'Crear nuevo prospecto. Si está offline, se envía en el batch de sync.',
                    'body'        => '{ "nombre": "...", "apellido": "...", "telefono": "...", "email": "...", "estado_prospecto": "nuevo" }',
                    'respuesta'   => '{ "data": { "id": 5, "folio": "...", ... } }',
                ],
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/contactos/{id}',
                    'descripcion' => 'Ver detalle de un prospecto.',
                    'body'        => '—',
                    'respuesta'   => '{ "data": { ... } }',
                ],
                [
                    'method'      => 'PUT',
                    'ruta'        => $base . '/contactos/{id}',
                    'descripcion' => 'Actualizar datos del prospecto.',
                    'body'        => '{ "estado_prospecto": "contactado", "notas": "..." }',
                    'respuesta'   => '{ "data": { ... } }',
                ],
            ],
            'Expedientes' => [
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/expedientes',
                    'descripcion' => 'Listar expedientes del asesor. Filtros: ?estado=en_proceso',
                    'body'        => '—',
                    'respuesta'   => '{ "data": [...] }',
                ],
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/expedientes',
                    'descripcion' => 'Crear expediente desde un prospecto.',
                    'body'        => '{ "contacto_id": 5, "tipo_tramite_id": 1, "monto_credito": 800000 }',
                    'respuesta'   => '{ "data": { "id": 12, "folio": "EXP-2025-0012", ... } }',
                ],
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/expedientes/{id}',
                    'descripcion' => 'Ver detalle del expediente con documentos y etapa actual.',
                    'body'        => '—',
                    'respuesta'   => '{ "data": { ... } }',
                ],
                [
                    'method'      => 'PUT',
                    'ruta'        => $base . '/expedientes/{id}',
                    'descripcion' => 'Actualizar etapa, estado o datos del expediente.',
                    'body'        => '{ "etapa_tramite_id": 3, "notas": "..." }',
                    'respuesta'   => '{ "data": { ... } }',
                ],
            ],
            'Documentos' => [
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/expedientes/{id}/documentos',
                    'descripcion' => 'Listar documentos de un expediente con estatus (pendiente / cargado / aprobado).',
                    'body'        => '—',
                    'respuesta'   => '{ "data": [...] }',
                ],
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/expedientes/{id}/documentos',
                    'descripcion' => 'Subir documento escaneado. Multipart/form-data. Máx. 10MB. Tipos: pdf, jpg, jpeg, png, heic.',
                    'body'        => 'Form-data: archivo (file), tipo_documento (string), notas (string, opcional)',
                    'respuesta'   => '{ "data": { "id": 8, "nombre": "...", "url": "...", "estado": "pendiente_revision" } }',
                ],
                [
                    'method'      => 'DELETE',
                    'ruta'        => $base . '/expedientes/{expId}/documentos/{docId}',
                    'descripcion' => 'Eliminar documento del expediente (solo el asesor propietario).',
                    'body'        => '—',
                    'respuesta'   => '{ "message": "Documento eliminado." }',
                ],
            ],
            'Ubicaciones y Visitas GPS' => [
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/ubicaciones',
                    'descripcion' => 'Registrar ubicación GPS de una visita a cliente o propiedad.',
                    'body'        => '{ "contacto_id": 5, "latitud": 20.1234, "longitud": -98.5678, "tipo": "visita_cliente|propiedad", "notas": "...", "visitado_en": "2025-05-17T10:30:00Z" }',
                    'respuesta'   => '{ "data": { "id": 3, ... } }',
                ],
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/ubicaciones',
                    'descripcion' => 'Listar ubicaciones registradas por el asesor. Filtros: ?fecha=2025-05-17&contacto_id=5',
                    'body'        => '—',
                    'respuesta'   => '{ "data": [...] }',
                ],
                [
                    'method'      => 'GET',
                    'ruta'        => $base . '/ubicaciones/mapa',
                    'descripcion' => 'Obtener todos los pins para el mapa CRM (solo super_admin). Incluye prospectos fríos.',
                    'body'        => '—',
                    'respuesta'   => '{ "data": [ { "lat": ..., "lng": ..., "tipo": "...", "contacto": {...} } ] }',
                ],
            ],
            'Sincronización Offline (Batch)' => [
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/sync',
                    'descripcion' => 'Enviar operaciones pendientes acumuladas sin conexión. El servidor las procesa en orden y devuelve resultados individuales.',
                    'body'        => implode(PHP_EOL, [
                        '{',
                        '  "operaciones": [',
                        '    { "id_local": "uuid-1", "tipo": "crear_contacto",   "datos": { ... } },',
                        '    { "id_local": "uuid-2", "tipo": "subir_documento",  "datos": { ... } },',
                        '    { "id_local": "uuid-3", "tipo": "registrar_ubicacion", "datos": { ... } }',
                        '  ]',
                        '}',
                    ]),
                    'respuesta'   => implode(PHP_EOL, [
                        '{',
                        '  "resultados": [',
                        '    { "id_local": "uuid-1", "estado": "ok",    "id_servidor": 42 },',
                        '    { "id_local": "uuid-2", "estado": "ok",    "id_servidor": 18 },',
                        '    { "id_local": "uuid-3", "estado": "error", "mensaje": "Contacto no encontrado" }',
                        '  ]',
                        '}',
                    ]),
                ],
            ],
            'Notificaciones Push' => [
                [
                    'method'      => 'POST',
                    'ruta'        => $base . '/dispositivos',
                    'descripcion' => 'Registrar token FCM del dispositivo para recibir push notifications.',
                    'body'        => '{ "fcm_token": "...", "plataforma": "android|ios" }',
                    'respuesta'   => '{ "message": "Dispositivo registrado." }',
                ],
                [
                    'method'      => 'DELETE',
                    'ruta'        => $base . '/dispositivos/{fcm_token}',
                    'descripcion' => 'Eliminar token del dispositivo al cerrar sesión.',
                    'body'        => '—',
                    'respuesta'   => '{ "message": "Dispositivo eliminado." }',
                ],
            ],
        ];
    }

    public function getMethodColor(string $method): string
    {
        return match($method) {
            'GET'    => 'text-green-600 bg-green-50',
            'POST'   => 'text-blue-600 bg-blue-50',
            'PUT'    => 'text-yellow-600 bg-yellow-50',
            'DELETE' => 'text-red-600 bg-red-50',
            default  => 'text-gray-600 bg-gray-50',
        };
    }
}
