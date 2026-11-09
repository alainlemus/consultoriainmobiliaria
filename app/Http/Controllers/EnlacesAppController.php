<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Archivos que iOS y Android leen del dominio para permitir que la liga del
 * QR (https://dominio/a/{codigo}) abra la app directamente cuando ya está
 * instalada, en vez del navegador.
 *
 *  - /.well-known/apple-app-site-association  (iOS, universal links)
 *  - /.well-known/assetlinks.json             (Android, app links)
 *
 * El Team ID de Apple y la huella SHA-256 del certificado de firma de Play se
 * configuran en Panel → App móvil. Sin huella, assetlinks regresa una lista
 * vacía (válido) y Android simplemente abre el navegador.
 */
class EnlacesAppController extends Controller
{
    public const BUNDLE_ID     = 'com.consultoriainmobiliaria.app';
    /** Team ID de la cuenta de Apple Developer (developer.apple.com → Membership). Se puede cambiar en Panel → App móvil. */
    public const APPLE_TEAM_ID = '7CKS7PY2UG';
    /** Huella SHA-256 del certificado de la clave de firma de Google Play (Play Console → Firma de apps). Se puede ampliar en Panel → App móvil. */
    public const ANDROID_SHA256 = '5A:2E:08:72:01:22:79:0F:CB:30:37:21:20:07:FF:EF:FB:E8:40:48:49:FE:06:99:0B:17:D2:E8:A4:1D:7E:0A';

    public function apple(): JsonResponse
    {
        $teamId = trim((string) setting('apple_team_id', self::APPLE_TEAM_ID));
        $appId  = $teamId !== '' ? "{$teamId}." . self::BUNDLE_ID : null;

        $payload = [
            'applinks' => [
                'details' => $appId ? [[
                    'appIDs'     => [$appId],
                    'components' => [
                        ['/' => '/a/*', 'comment' => 'Liga del QR de asesor'],
                    ],
                ]] : [],
            ],
        ];

        // Apple exige application/json sin redirecciones; los encabezados de
        // caché cortos permiten corregir el Team ID sin esperar.
        return response()->json($payload)->header('Cache-Control', 'public, max-age=3600');
    }

    public function android(): JsonResponse
    {
        $huellas = collect(preg_split('/[\s,;]+/', (string) setting('android_sha256', self::ANDROID_SHA256)))
            ->map(fn ($h) => strtoupper(trim($h)))
            ->filter(fn ($h) => preg_match('/^([0-9A-F]{2}:){31}[0-9A-F]{2}$/', $h))
            ->values();

        $payload = $huellas->isEmpty() ? [] : [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target'   => [
                'namespace'                => 'android_app',
                'package_name'             => self::BUNDLE_ID,
                'sha256_cert_fingerprints' => $huellas->all(),
            ],
        ]];

        return response()->json($payload)->header('Cache-Control', 'public, max-age=3600');
    }
}
