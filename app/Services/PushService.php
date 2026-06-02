<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para enviar Push Notifications.
 *
 * Detecta automáticamente el tipo de token:
 *   - ExponentPushToken[...] → Expo Push API
 *   - Cualquier otro          → FCM HTTP v1 (nativo)
 */
class PushService
{
    private const EXPO_URL = 'https://exp.host/--/api/v2/push/send';
    private const FCM_URL  = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';
    private const TOKEN_URL   = 'https://oauth2.googleapis.com/token';
    private const SCOPE       = 'https://www.googleapis.com/auth/firebase.messaging';
    private const CACHE_KEY   = 'fcm_access_token';

    /**
     * Envía una push notification a todos los dispositivos de un usuario.
     */
    public static function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::where('user_id', $user->id)
            ->pluck('fcm_token')
            ->toArray();

        foreach ($tokens as $token) {
            static::sendToToken($token, $title, $body, $data);
        }
    }

    /**
     * Envía una push notification a un token específico.
     * Detecta automáticamente si es Expo o FCM nativo.
     */
    public static function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        if (str_starts_with($token, 'ExponentPushToken')) {
            static::sendViaExpo($token, $title, $body, $data);
        } else {
            static::sendViaFcm($token, $title, $body, $data);
        }
    }

    // ── Expo Push API ─────────────────────────────────────────────────────────

    private static function sendViaExpo(string $token, string $title, string $body, array $data = []): void
    {
        $response = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(10)->post(static::EXPO_URL, [
            'to'    => $token,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
            'sound' => 'default',
            'badge' => 1,
        ]);

        if ($response->failed()) {
            Log::warning("[Expo Push] Error enviando a {$token}: " . $response->body());
            return;
        }

        $result   = $response->json('data');
        $status   = $result['status']  ?? 'unknown';
        $ticketId = $result['id']      ?? null;

        if ($status === 'error') {
            $details   = $result['details'] ?? [];
            $errorCode = $details['error']  ?? $result['message'] ?? 'unknown';
            Log::warning("[Expo Push] Error en respuesta para {$token}: {$errorCode}");

            if (in_array($errorCode, ['DeviceNotRegistered', 'InvalidCredentials'])) {
                DeviceToken::where('fcm_token', $token)->delete();
                Log::info("[Expo Push] Token inválido eliminado: {$token}");
            }
        } else {
            Log::info("[Expo Push] Ticket OK para {$token} — ticketId: {$ticketId}");

            // Verificar receipt para confirmar entrega APNs/FCM
            if ($ticketId) {
                sleep(3); // Expo tarda ~2s en procesar
                $receiptResponse = Http::withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])->timeout(10)->post('https://exp.host/--/api/v2/push/getReceipts', [
                    'ids' => [$ticketId],
                ]);

                if ($receiptResponse->ok()) {
                    $receipt = $receiptResponse->json("data.{$ticketId}") ?? [];
                    $receiptStatus = $receipt['status'] ?? 'unknown';

                    if ($receiptStatus === 'error') {
                        $receiptError = $receipt['details']['error'] ?? $receipt['message'] ?? 'unknown';
                        Log::warning("[Expo Push] Receipt ERROR para ticketId {$ticketId}: {$receiptError}");
                    } else {
                        Log::info("[Expo Push] Receipt OK — notificación entregada a APNs/FCM (ticketId: {$ticketId})");
                    }
                }
            }
        }
    }

    // ── FCM HTTP v1 ───────────────────────────────────────────────────────────

    private static function sendViaFcm(string $token, string $title, string $body, array $data = []): void
    {
        $projectId = config('services.firebase.project_id');

        if (! $projectId) {
            Log::warning('[FCM] FIREBASE_PROJECT_ID no configurado.');
            return;
        }

        $accessToken = static::getAccessToken();
        if (! $accessToken) {
            return;
        }

        $payload = [
            'message' => [
                'token'        => $token,
                'notification' => ['title' => $title, 'body' => $body],
                'data'         => collect($data)->map(fn ($v) => (string) $v)->toArray(),
                'android'      => [
                    'priority'     => 'high',
                    'notification' => ['sound' => 'default', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK'],
                ],
                'apns' => [
                    'payload' => ['aps' => ['sound' => 'default', 'badge' => 1]],
                ],
            ],
        ];

        $url      = sprintf(static::FCM_URL, $projectId);
        $response = Http::withToken($accessToken)->timeout(10)->post($url, $payload);

        if ($response->failed()) {
            $error = $response->json('error.message') ?? $response->body();
            Log::warning("[FCM] Error enviando push a token {$token}: {$error}");

            if (str_contains($error, 'UNREGISTERED') || str_contains($error, 'INVALID_ARGUMENT')) {
                DeviceToken::where('fcm_token', $token)->delete();
                Log::info("[FCM] Token inválido eliminado: {$token}");
            }
        }
    }

    // ── OAuth2 para FCM ───────────────────────────────────────────────────────

    private static function getAccessToken(): ?string
    {
        return \Illuminate\Support\Facades\Cache::remember(static::CACHE_KEY, 3000, function () {
            $credentialsPath = config('services.firebase.credentials');

            if (! $credentialsPath || ! file_exists($credentialsPath)) {
                Log::warning('[FCM] FIREBASE_CREDENTIALS no configurado o archivo no existe.');
                return null;
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            if (! isset($credentials['client_email'], $credentials['private_key'])) {
                Log::error('[FCM] Archivo de credenciales Firebase inválido.');
                return null;
            }

            $jwt      = static::buildJwt($credentials['client_email'], $credentials['private_key']);
            $response = Http::asForm()->post(static::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->failed()) {
                Log::error('[FCM] Error obteniendo access token: ' . $response->body());
                return null;
            }

            return $response->json('access_token');
        });
    }

    private static function buildJwt(string $clientEmail, string $privateKey): string
    {
        $now = time();
        $exp = $now + 3600;

        $header  = static::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = static::base64UrlEncode(json_encode([
            'iss'   => $clientEmail,
            'scope' => static::SCOPE,
            'aud'   => static::TOKEN_URL,
            'exp'   => $exp,
            'iat'   => $now,
        ]));

        $signingInput = "{$header}.{$payload}";
        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . static::base64UrlEncode($signature);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
