<?php

namespace App\Filament\Pages;

use App\Models\Configuracion;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSettings extends Page
{
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    protected static string|\BackedEnum|null $navigationIcon  = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $navigationLabel = 'WhatsApp Configuración';
    protected static ?string $title           = 'Configuración WhatsApp';
    protected static string|\UnitEnum|null $navigationGroup  = 'Configuración CRM';
    protected static ?int $navigationSort     = 20;
    protected string $view = 'filament.pages.whatsapp-settings';

    public ?array $data = [];

    // Para la sección de sesiones (fuera del form)
    public array $sesiones      = [];
    public ?string $qrCodeData  = null;
    public ?string $qrSesionId  = null;
    public string $nuevaSesionNombre = '';

    public function mount(): void
    {
        $this->form->fill([
            'owa_session_id'   => setting('owa_session_id', config('services.openwa.session')),
            'owa_telefono'     => setting('owa_telefono'),
            'owa_webhook_host' => setting('owa_webhook_host'),
            'owa_webhook_port' => setting('owa_webhook_port', '8000'),
            'owa_webhook_id'   => setting('owa_webhook_id'),
        ]);

        $this->cargarSesiones();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Sesión activa')
                    ->description('Sesión de OpenWA que recibe y envía mensajes. Cámbiala si conectas un nuevo teléfono.')
                    ->schema([
                        Forms\Components\TextInput::make('owa_session_id')
                            ->label('Session ID')
                            ->placeholder('ej: 465c0ca6-4846-4e25-9c57-3c902a011ddc')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('owa_telefono')
                            ->label('Teléfono (referencia)')
                            ->placeholder('ej: 5215531293712')
                            ->helperText('Solo informativo.')
                            ->maxLength(20),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Servidor CRM')
                    ->description('URL interna donde OpenWA entrega los mensajes entrantes. Se detecta automáticamente con el botón.')
                    ->schema([
                        Forms\Components\TextInput::make('owa_webhook_host')
                            ->label('Hostname del contenedor')
                            ->placeholder('ej: eac0b6eeada9')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('owa_webhook_port')
                            ->label('Puerto')
                            ->default('8000')
                            ->required()
                            ->maxLength(10),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\TextInput::make('owa_webhook_id')
                            ->label('ID del webhook activo')
                            ->disabled()
                            ->helperText('Se actualiza automáticamente al guardar.')
                            ->maxLength(100),
                    ]),
            ])
            ->statePath('data');
    }

    // ──────────────────────────────────────────────
    // ACCIONES
    // ──────────────────────────────────────────────

    /**
     * Auto-detecta el hostname del contenedor actual usando PHP.
     */
    public function detectarHostname(): void
    {
        $hostname = gethostname();
        $this->data['owa_webhook_host'] = $hostname;

        Notification::make()
            ->title("Hostname detectado: {$hostname}")
            ->success()
            ->send();
    }

    /**
     * Guarda la configuración y actualiza el webhook en OpenWA.
     */
    public function save(): void
    {
        $state = $this->form->getState();

        $sessionId = trim($state['owa_session_id']);
        $host      = trim($state['owa_webhook_host']);
        $port      = trim($state['owa_webhook_port'] ?? '8000');
        $url       = "http://{$host}:{$port}/api/webhooks/whatsapp";

        // Eliminar webhook anterior
        $webhookIdAnterior = setting('owa_webhook_id');
        $sessionAnterior   = setting('owa_session_id', config('services.openwa.session'));
        if ($webhookIdAnterior) {
            $this->eliminarWebhook($webhookIdAnterior, $sessionAnterior);
        }

        // Registrar nuevo webhook
        $nuevoId = $this->registrarWebhook($url, $sessionId);

        if (! $nuevoId) {
            Notification::make()
                ->title('No se pudo registrar el webhook en OpenWA.')
                ->body("Session ID: {$sessionId} | URL: {$url}")
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        Configuracion::set('owa_session_id',  $sessionId);
        Configuracion::set('owa_telefono',     trim($state['owa_telefono'] ?? ''));
        Configuracion::set('owa_webhook_host', $host);
        Configuracion::set('owa_webhook_port', $port);
        Configuracion::set('owa_webhook_id',   $nuevoId);

        $this->data['owa_webhook_id'] = $nuevoId;

        Notification::make()
            ->title('Configuración de WhatsApp guardada.')
            ->body("Webhook activo: {$url}")
            ->success()
            ->send();

        Log::info("[WhatsApp Settings] Webhook → {$url} | Sesión: {$sessionId} | ID: {$nuevoId}");
    }

    /**
     * Recarga la lista de sesiones desde OpenWA.
     */
    public function cargarSesiones(): void
    {
        try {
            $response = Http::withHeader('x-api-key', config('services.openwa.api_key'))
                ->timeout(5)
                ->get(config('services.openwa.url') . '/api/sessions');

            $this->sesiones = $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable) {
            $this->sesiones = [];
        }
    }

    /**
     * Crea una nueva sesión en OpenWA y obtiene el QR.
     */
    public function crearSesion(): void
    {
        $nombre = trim($this->nuevaSesionNombre);
        if (! $nombre) {
            Notification::make()->title('Escribe un nombre para la sesión.')->warning()->send();
            return;
        }

        try {
            $apiKey  = config('services.openwa.api_key');
            $baseUrl = config('services.openwa.url');

            // 1. Crear sesión
            $resp = Http::withHeader('x-api-key', $apiKey)
                ->timeout(10)
                ->post("{$baseUrl}/api/sessions", ['name' => $nombre]);

            if (! $resp->successful()) {
                $msg = $resp->status() === 400
                    ? 'Ya existe una sesión con ese nombre. Usa un nombre diferente.'
                    : 'Error al crear la sesión: ' . $resp->body();
                Notification::make()->title($msg)->danger()->send();
                return;
            }

            $sesionId = $resp->json('id');

            // 2. Iniciar la sesión (necesario antes de pedir el QR)
            Http::withHeader('x-api-key', $apiKey)
                ->timeout(10)
                ->post("{$baseUrl}/api/sessions/{$sesionId}/start");

            // 3. Esperar a que arranque y pedir el QR (reintentar hasta 3 veces)
            $qrData = null;
            for ($i = 0; $i < 3; $i++) {
                sleep(3);
                $qrResp = Http::withHeader('x-api-key', $apiKey)
                    ->timeout(10)
                    ->get("{$baseUrl}/api/sessions/{$sesionId}/qr");

                if ($qrResp->successful()) {
                    $qrData = $qrResp->json('qrCode') ?? $qrResp->json('value') ?? $qrResp->json('qr');
                    if ($qrData) break;
                }
            }

            $this->qrSesionId = $sesionId;
            $this->nuevaSesionNombre = '';
            $this->cargarSesiones();

            if ($qrData) {
                $this->qrCodeData = $qrData;
                Notification::make()->title('Sesión creada. Escanea el QR con WhatsApp.')->success()->send();
            } else {
                Notification::make()
                    ->title('Sesión creada.')
                    ->body('El QR está cargando. Presiona "Ver QR" en unos segundos.')
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    /**
     * Obtiene el QR de una sesión existente.
     */
    public function obtenerQr(string $sesionId): void
    {
        try {
            $apiKey  = config('services.openwa.api_key');
            $baseUrl = config('services.openwa.url');

            // Verificar estado — si no está inicializada, arrancarla primero
            $sesion = Http::withHeader('x-api-key', $apiKey)
                ->timeout(5)
                ->get("{$baseUrl}/api/sessions/{$sesionId}")
                ->json();

            $status = $sesion['status'] ?? '';
            if (in_array($status, ['created', 'stopped', 'failed'])) {
                Http::withHeader('x-api-key', $apiKey)
                    ->timeout(10)
                    ->post("{$baseUrl}/api/sessions/{$sesionId}/start");
                sleep(3);
            }

            $response = Http::withHeader('x-api-key', $apiKey)
                ->timeout(10)
                ->get("{$baseUrl}/api/sessions/{$sesionId}/qr");

            if ($response->successful()) {
                $this->qrCodeData = $response->json('qrCode') ?? $response->json('value') ?? $response->json('qr');
                $this->qrSesionId = $sesionId;

                if (! $this->qrCodeData) {
                    Notification::make()->title('Sesión iniciando, intenta de nuevo en unos segundos.')->warning()->send();
                }
            } else {
                Notification::make()->title('No se pudo obtener el QR: ' . $response->body())->danger()->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    /**
     * Usa una sesión existente como sesión activa del CRM.
     */
    public function usarSesion(string $sesionId, string $telefono = ''): void
    {
        $this->data['owa_session_id'] = $sesionId;
        $this->data['owa_telefono']   = $telefono;

        Notification::make()
            ->title("Sesión {$sesionId} seleccionada.")
            ->body('Presiona "Guardar y actualizar webhook" para aplicar el cambio.')
            ->info()
            ->send();
    }

    /**
     * Elimina una sesión de OpenWA.
     */
    public function desconectarSesion(string $sesionId): void
    {
        try {
            $apiKey  = config('services.openwa.api_key');
            $baseUrl = config('services.openwa.url');

            Http::withHeader('x-api-key', $apiKey)
                ->timeout(5)
                ->post("{$baseUrl}/api/sessions/{$sesionId}/stop");

            $this->cargarSesiones();
            Notification::make()->title('Sesión desconectada. Vincula un nuevo número con Ver QR.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function eliminarSesion(string $sesionId): void
    {
        // No permitir eliminar la sesión activa
        $sesionActiva = setting('owa_session_id', config('services.openwa.session'));
        if ($sesionId === $sesionActiva) {
            Notification::make()->title('No puedes eliminar la sesión activa.')->warning()->send();
            return;
        }

        try {
            $response = Http::withHeader('x-api-key', config('services.openwa.api_key'))
                ->timeout(5)
                ->delete(config('services.openwa.url') . "/api/sessions/{$sesionId}");

            // 204 No Content = éxito
            if ($response->status() === 204 || $response->successful()) {
                $this->cargarSesiones();
                Notification::make()->title('Sesión eliminada correctamente.')->success()->send();
            } else {
                Notification::make()
                    ->title('No se pudo eliminar la sesión.')
                    ->body('Código: ' . $response->status() . ' — ' . $response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    // ──────────────────────────────────────────────
    // HELPERS OPENWA
    // ──────────────────────────────────────────────

    private function eliminarWebhook(string $webhookId, string $sessionId): void
    {
        try {
            Http::withHeader('x-api-key', config('services.openwa.api_key'))
                ->timeout(5)
                ->delete(config('services.openwa.url') . "/api/sessions/{$sessionId}/webhooks/{$webhookId}");
        } catch (\Throwable $e) {
            Log::warning("[WhatsApp Settings] No se pudo eliminar webhook {$webhookId}: " . $e->getMessage());
        }
    }

    private function registrarWebhook(string $url, string $sessionId): ?string
    {
        try {
            $apiKey  = config('services.openwa.api_key');
            $baseUrl = config('services.openwa.url');

            $response = Http::withHeader('x-api-key', $apiKey)
                ->timeout(10)
                ->post("{$baseUrl}/api/sessions/{$sessionId}/webhooks", [
                    'url'    => $url,
                    'events' => ['message.received'],
                ]);

            Log::info("[WhatsApp Settings] Registrar webhook response: " . $response->status() . " — " . $response->body());

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::warning("[WhatsApp Settings] Error al registrar webhook ({$response->status()}): " . $response->body());
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Settings] Excepción: " . $e->getMessage());
        }

        return null;
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
