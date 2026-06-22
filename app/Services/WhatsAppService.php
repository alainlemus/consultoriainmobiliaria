<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private static function baseUrl(): string
    {
        return rtrim(config('services.openwa.url', 'http://localhost:2785'), '/');
    }

    private static function apiKey(): string
    {
        return config('services.openwa.api_key', '');
    }

    private static function session(): string
    {
        // BD tiene prioridad (configurable desde el panel) → fallback a .env
        return setting('owa_session_id') ?: config('services.openwa.session', '');
    }

    /**
     * Envía un mensaje de texto a un número de teléfono.
     * El número debe ser formato mexicano: 52XXXXXXXXXX (sin +)
     */
    public static function sendText(string $phone, string $message): bool
    {
        // ── Interruptor global: si está desactivado, no envía nada ──────────
        if (! (bool) setting('whatsapp_habilitado', true)) {
            Log::info("[WhatsApp] Envío DESACTIVADO — mensaje bloqueado para {$phone}");
            return false;
        }

        $chatId = static::formatChatId($phone);

        if (! $chatId) {
            Log::warning("[WhatsApp] Número inválido: {$phone}");
            return false;
        }

        $session = static::session();
        if (! $session) {
            Log::warning('[WhatsApp] OPENWA_SESSION no configurado.');
            return false;
        }

        $url = static::baseUrl() . "/api/sessions/{$session}/messages/send-text";

        try {
            $response = Http::withHeaders([
                'X-API-Key'    => static::apiKey(),
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, [
                'chatId' => $chatId,
                'text'   => $message,
            ]);

            if ($response->successful()) {
                Log::info("[WhatsApp] Mensaje enviado a {$chatId}");
                return true;
            }

            Log::warning("[WhatsApp] Error enviando a {$chatId}: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("[WhatsApp] Excepción al enviar a {$chatId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convierte un teléfono mexicano al formato chatId de WhatsApp.
     * Ejemplos:
     *   5531293719   → 525531293719@c.us
     *   525531293719 → 525531293719@c.us
     *   +525531293719 → 525531293719@c.us
     */
    public static function formatChatId(string $phone): ?string
    {
        // Limpiar caracteres no numéricos
        $clean = preg_replace('/\D/', '', $phone);

        if (! $clean) return null;

        // 13 dígitos: 521XXXXXXXXXX — formato real de WhatsApp México
        if (strlen($clean) === 13 && str_starts_with($clean, '521')) {
            return "{$clean}@c.us";
        }

        // 10 dígitos (número local mexicano): agregar 521
        if (strlen($clean) === 10) {
            return "521{$clean}@c.us";
        }

        // 12 dígitos 52XXXXXXXXXX: insertar el 1
        if (strlen($clean) === 12 && str_starts_with($clean, '52')) {
            return '521' . substr($clean, 2) . '@c.us';
        }

        Log::warning("[WhatsApp] Teléfono con formato inesperado: {$phone} ({$clean})");
        return null;
    }

    /**
     * Verifica si la sesión de OpenWA está activa.
     */
    public static function isSessionReady(): bool
    {
        $session = static::session();
        if (! $session) return false;

        try {
            $response = Http::withHeaders(['X-API-Key' => static::apiKey()])
                ->timeout(5)
                ->get(static::baseUrl() . "/api/sessions/{$session}");

            return $response->successful()
                && ($response->json('status') === 'ready');
        } catch (\Exception) {
            return false;
        }
    }
}
