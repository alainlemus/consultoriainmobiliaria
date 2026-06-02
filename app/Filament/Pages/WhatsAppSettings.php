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
    protected static ?string $navigationLabel = 'WhatsApp';
    protected static ?string $title           = 'Configuración WhatsApp';
    protected static string|\UnitEnum|null $navigationGroup  = 'Configuración del sitio';
    protected static ?int $navigationSort     = 50;
    protected string $view = 'filament.pages.whatsapp-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'owa_session_id'   => setting('owa_session_id', config('services.openwa.session')),
            'owa_telefono'     => setting('owa_telefono'),
            'owa_webhook_host' => setting('owa_webhook_host'),
            'owa_webhook_port' => setting('owa_webhook_port', '8000'),
            'owa_webhook_id'   => setting('owa_webhook_id'),
            'owa_url'          => config('services.openwa.url'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Sesión de WhatsApp')
                    ->description('Cuando cambias de teléfono en OpenWA se genera una nueva sesión. Actualiza el Session ID aquí y guarda para que el sistema use el número nuevo.')
                    ->schema([
                        Forms\Components\TextInput::make('owa_session_id')
                            ->label('Session ID de OpenWA')
                            ->placeholder('ej: 465c0ca6-4846-4e25-9c57-3c902a011ddc')
                            ->helperText('Encuéntralo en el dashboard de OpenWA → Sessions.')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('owa_telefono')
                            ->label('Número de teléfono (referencia)')
                            ->placeholder('ej: 5215531293712')
                            ->helperText('Solo informativo. Formato: código de país + número.')
                            ->maxLength(20),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Servidor CRM')
                    ->description('Cada deploy cambia el hostname del contenedor. Actualízalo aquí y guarda para que OpenWA sepa a dónde enviar los mensajes entrantes.')
                    ->schema([
                        Forms\Components\TextInput::make('owa_webhook_host')
                            ->label('Hostname del contenedor')
                            ->placeholder('ej: eac0b6eeada9')
                            ->helperText('Corre `cat /etc/hostname` dentro del contenedor para obtenerlo.')
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

                        Forms\Components\TextInput::make('owa_url')
                            ->label('URL de la API de OpenWA')
                            ->disabled(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $sessionId = trim($state['owa_session_id']);
        $host      = trim($state['owa_webhook_host']);
        $port      = trim($state['owa_webhook_port'] ?? '8000');
        $url       = "http://{$host}:{$port}/api/webhooks/whatsapp";

        // 1. Eliminar webhook anterior si existe
        $webhookIdAnterior  = setting('owa_webhook_id');
        $sessionIdAnterior  = setting('owa_session_id', config('services.openwa.session'));
        if ($webhookIdAnterior) {
            $this->eliminarWebhook($webhookIdAnterior, $sessionIdAnterior);
        }

        // 2. Registrar nuevo webhook en OpenWA con la sesión (nueva o misma)
        $nuevoId = $this->registrarWebhook($url, $sessionId);

        if (! $nuevoId) {
            Notification::make()
                ->title('No se pudo registrar el webhook en OpenWA.')
                ->body('Verifica que el hostname y el Session ID sean correctos.')
                ->danger()
                ->send();
            return;
        }

        // 3. Guardar todo en BD
        Configuracion::set('owa_session_id',   $sessionId);
        Configuracion::set('owa_telefono',      trim($state['owa_telefono'] ?? ''));
        Configuracion::set('owa_webhook_host',  $host);
        Configuracion::set('owa_webhook_port',  $port);
        Configuracion::set('owa_webhook_id',    $nuevoId);

        $this->data['owa_webhook_id'] = $nuevoId;

        Notification::make()
            ->title('Configuración de WhatsApp guardada.')
            ->body("Webhook activo: {$url}")
            ->success()
            ->send();

        Log::info("[WhatsApp Settings] Webhook actualizado → {$url} | Sesión: {$sessionId} | ID: {$nuevoId}");
    }

    // ──────────────────────────────────────────────
    // HELPERS OPENWA
    // ──────────────────────────────────────────────

    private function eliminarWebhook(string $webhookId, string $sessionId): void
    {
        try {
            $apiKey  = config('services.openwa.api_key');
            $baseUrl = config('services.openwa.url');

            Http::withHeader('x-api-key', $apiKey)
                ->timeout(5)
                ->delete("{$baseUrl}/api/sessions/{$sessionId}/webhooks/{$webhookId}");
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
                ->timeout(5)
                ->post("{$baseUrl}/api/sessions/{$sessionId}/webhooks", [
                    'url'    => $url,
                    'events' => ['message.received'],
                ]);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::warning("[WhatsApp Settings] Error al registrar webhook: " . $response->body());
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Settings] Excepción al registrar webhook: " . $e->getMessage());
        }

        return null;
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
