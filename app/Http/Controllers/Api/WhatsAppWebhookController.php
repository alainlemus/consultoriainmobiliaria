<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsappChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private WhatsappChatbotService $chatbot
    ) {}

    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        // Verificar secret si está configurado
        $secret = config('services.openwa.webhook_secret');
        if ($secret) {
            $signature = $request->header('X-Webhook-Signature');
            $expected  = hash_hmac('sha256', $request->getContent(), $secret);
            if (! hash_equals($expected, (string) $signature)) {
                Log::warning('[WhatsApp Webhook] Firma inválida');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $event = $request->input('event');
        $data  = $request->input('data', []);

        Log::info("[WhatsApp Webhook] Evento: {$event}");

        if ($event === 'message.received') {
            $this->procesarMensajeEntrante($data);
        }

        return response()->json(['ok' => true]);
    }

    private function procesarMensajeEntrante(array $data): void
    {
        $chatId = $data['from'] ?? $data['chatId'] ?? null;
        if (! $chatId) return;

        // Ignorar grupos
        if (str_contains($chatId, '@g.us')) return;

        // Ignorar mensajes propios
        if (($data['fromMe'] ?? false) === true) return;

        // Ignorar si no hay texto (stickers, imágenes, etc.)
        $mensaje = trim($data['body'] ?? '');

        // Si viene en formato @lid, resolver al @c.us real
        $pushName = $data['notifyName'] ?? $data['pushName'] ?? null;
        if (str_contains($chatId, '@lid')) {
            $resolved = $this->resolverLidACus($chatId);
            if (! $resolved) return;
            $chatId   = $resolved['id'];
            if (! $pushName) {
                $pushName = $resolved['pushName'] ?? $resolved['name'] ?? null;
            }
        }

        if (! str_contains($chatId, '@c.us')) return;

        $telefono = $this->extraerTelefono($chatId);
        if (! $telefono) return;

        // Delegar al chatbot (maneja estado, flujo y creación del prospecto)
        $this->chatbot->procesar($chatId, $telefono, $mensaje ?: null, $pushName);
    }

    private function resolverLidACus(string $lidChatId): ?array
    {
        try {
            $sessionId = setting('owa_session_id') ?: config('services.openwa.session');
            $apiKey    = config('services.openwa.api_key');
            $url       = config('services.openwa.url');

            $response = Http::withHeader('x-api-key', $apiKey)
                ->timeout(5)
                ->get("{$url}/api/sessions/{$sessionId}/contacts/{$lidChatId}");

            if ($response->successful()) {
                $contact   = $response->json();
                $contactId = $contact['id'] ?? null;
                if ($contactId && str_contains($contactId, '@c.us')) {
                    return [
                        'id'       => $contactId,
                        'name'     => $contact['name'] ?? null,
                        'pushName' => $contact['pushName'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[WhatsApp Webhook] No se pudo resolver @lid {$lidChatId}: " . $e->getMessage());
        }

        return null;
    }

    private function extraerTelefono(string $chatId): ?string
    {
        $numero = explode('@', $chatId)[0] ?? null;
        if (! $numero || ! is_numeric($numero)) return null;

        if (strlen($numero) === 13 && str_starts_with($numero, '521')) {
            return substr($numero, 3);
        }
        if (strlen($numero) === 12 && str_starts_with($numero, '52')) {
            return substr($numero, 2);
        }
        if (strlen($numero) === 10) {
            return $numero;
        }

        return $numero;
    }
}
