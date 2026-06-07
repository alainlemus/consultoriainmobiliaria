<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActualizarWebhookHostname extends Command
{
    protected $signature   = 'whatsapp:actualizar-webhook';
    protected $description = 'Auto-detecta el hostname del contenedor y actualiza el webhook en OpenWA';

    public function __construct(private WhatsAppService $whatsapp)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $hostname  = gethostname();
        $port      = config('services.openwa.webhook_port', '8000');
        $url       = config('services.openwa.url');
        $apiKey    = config('services.openwa.api_key');
        $sessionId = setting('owa_session_id') ?: config('services.openwa.session');

        if (! $hostname || ! $url || ! $apiKey || ! $sessionId) {
            $this->error('Faltan variables de configuración (url, api_key, session_id).');
            return self::FAILURE;
        }

        $webhookUrl = "http://{$hostname}:{$port}/api/webhooks/whatsapp";
        $this->info("Hostname detectado: {$hostname}");
        $this->info("Webhook URL: {$webhookUrl}");

        // Eliminar webhook anterior
        $webhookIdAnterior = setting('owa_webhook_id');
        if ($webhookIdAnterior) {
            Http::withHeader('x-api-key', $apiKey)
                ->timeout(5)
                ->delete("{$url}/api/sessions/{$sessionId}/webhooks/{$webhookIdAnterior}");
            $this->info("Webhook anterior eliminado: {$webhookIdAnterior}");
        }

        // Registrar nuevo webhook
        $response = Http::withHeader('x-api-key', $apiKey)
            ->timeout(10)
            ->post("{$url}/api/sessions/{$sessionId}/webhooks", [
                'url'    => $webhookUrl,
                'events' => ['message.received'],
            ]);

        if (! $response->successful()) {
            $this->error("Error al registrar webhook: " . $response->body());
            Log::error('[ActualizarWebhookHostname] ' . $response->body());
            return self::FAILURE;
        }

        $nuevoId = $response->json('id');
        Configuracion::set('owa_webhook_id',   $nuevoId);
        Configuracion::set('owa_webhook_host',  $hostname);
        Configuracion::set('owa_webhook_port',  $port);

        $this->info("Webhook registrado correctamente. ID: {$nuevoId}");
        Log::info("[ActualizarWebhookHostname] Webhook actualizado. Hostname: {$hostname}, ID: {$nuevoId}");

        return self::SUCCESS;
    }
}
