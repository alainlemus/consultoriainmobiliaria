<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Recibe eventos de OpenWA vía webhook.
     * Solo procesa mensajes entrantes de números desconocidos (nuevos prospectos).
     */
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

        // Log completo para debugging de estructura del payload
        Log::info("[WhatsApp Webhook] Payload completo: " . json_encode($request->all()));

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

        // Ignorar mensajes propios (enviados por nosotros)
        if (($data['fromMe'] ?? false) === true) return;

        // Si viene en formato @lid, resolver al @c.us real vía API de contactos
        if (str_contains($chatId, '@lid')) {
            $chatId = $this->resolverLidACus($chatId);
            if (! $chatId) return;
        }

        if (! str_contains($chatId, '@c.us')) return;

        // Extraer teléfono limpio: "521XXXXXXXXXX@c.us" → "5531293712"
        $telefono = $this->extraerTelefono($chatId);
        if (! $telefono) return;

        $pushName = $data['notifyName'] ?? $data['pushName'] ?? null;

        // Verificar si ya existe un contacto con ese teléfono
        $existe = Contacto::where('telefono', $telefono)->exists();

        if ($existe) {
            Log::info("[WhatsApp Webhook] Contacto ya existe para {$telefono}, ignorando.");
            return;
        }

        // Crear nuevo prospecto
        $nombre = $this->parsearNombre($pushName);

        $contacto = Contacto::create([
            'nombre'                => $nombre['nombre'],
            'apellidos'             => $nombre['apellidos'],
            'telefono'              => $telefono,
            'origen'                => 'whatsapp',
            'estado_prospecto'      => 'nuevo',
            'fecha_primer_contacto' => now()->toDateString(),
            'notas'                 => "Prospecto generado automáticamente desde WhatsApp.\nPrimer mensaje: " . ($data['body'] ?? '—'),
        ]);

        Log::info("[WhatsApp Webhook] Nuevo prospecto creado: {$contacto->id} — {$telefono}");
    }

    private function resolverLidACus(string $lidChatId): ?string
    {
        try {
            $sessionId = config('services.openwa.session');
            $apiKey    = config('services.openwa.api_key');
            $url       = config('services.openwa.url');

            $response = \Illuminate\Support\Facades\Http::withHeader('x-api-key', $apiKey)
                ->timeout(5)
                ->get("{$url}/api/sessions/{$sessionId}/contacts/{$lidChatId}");

            if ($response->successful()) {
                $contactId = $response->json('id'); // ej: "5217751557436@c.us"
                if ($contactId && str_contains($contactId, '@c.us')) {
                    Log::info("[WhatsApp Webhook] @lid {$lidChatId} resuelto a {$contactId}");
                    return $contactId;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[WhatsApp Webhook] No se pudo resolver @lid {$lidChatId}: " . $e->getMessage());
        }

        return null;
    }

    private function extraerTelefono(string $chatId): ?string
    {
        // chatId formato: "521XXXXXXXXXX@c.us"
        $numero = explode('@', $chatId)[0] ?? null;
        if (! $numero || ! is_numeric($numero)) return null;

        // Normalizar a 10 dígitos mexicanos
        if (strlen($numero) === 13 && str_starts_with($numero, '521')) {
            return substr($numero, 3); // quitar 521
        }
        if (strlen($numero) === 12 && str_starts_with($numero, '52')) {
            return substr($numero, 2); // quitar 52
        }
        if (strlen($numero) === 10) {
            return $numero;
        }

        return $numero; // devolver tal cual si no es formato mexicano
    }

    private function parsearNombre(?string $pushName): array
    {
        if (! $pushName) {
            return ['nombre' => 'Prospecto', 'apellidos' => 'WhatsApp'];
        }

        $partes = explode(' ', trim($pushName), 2);
        return [
            'nombre'    => $partes[0],
            'apellidos' => $partes[1] ?? '',
        ];
    }
}
