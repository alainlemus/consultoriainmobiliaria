<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use App\Models\EscaneoQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /v1/acreditado/atribucion  (público, una vez por instalación)
 *
 * La app lo llama en el primer arranque, antes de que exista cuenta, para
 * averiguar de qué QR vino la instalación cuando la tienda no se lo dijo
 * (siempre en iPhone; en Android solo si faltó el Install Referrer).
 *
 * Cruce por huella: mismo IP público, misma plataforma, misma versión mayor
 * del sistema (si el navegador la declaró) y, si se puede, mismo modelo, en
 * las últimas 48 h. Es "probable", no exacto: si en esa ventana hubo
 * escaneos de QR distintos desde la misma IP (NAT de celular compartido),
 * no se adivina y la persona teclea el código impreso.
 */
class AtribucionController extends Controller
{
    public const VENTANA_HORAS = 48;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plataforma' => ['required', 'in:android,ios'],
            'os_version' => ['nullable', 'string', 'max:20'],
            'modelo'     => ['nullable', 'string', 'max:60'],
        ]);

        $candidatos = EscaneoQr::query()
            ->with('codigoQr.asesor')
            ->where('ip', $request->ip())
            ->where('plataforma', $data['plataforma'])
            ->whereNull('atribuido_at')
            ->where('created_at', '>=', now()->subHours(self::VENTANA_HORAS))
            ->whereHas('codigoQr', fn ($q) => $q->where('activo', true))
            ->latest('created_at')
            ->get();

        // Versión mayor del sistema: si el UA la trae y no coincide, fuera.
        $versionMayor = ! empty($data['os_version']) ? (int) explode('.', $data['os_version'])[0] : null;
        if ($versionMayor) {
            $candidatos = $candidatos->filter(function (EscaneoQr $e) use ($versionMayor) {
                $v = $e->versionSoMayor();
                return $v === null || $v === $versionMayor;
            });
        }

        // Modelo: Chrome Android lo incluye en el UA; si alguno coincide, nos quedamos solo con esos.
        if (! empty($data['modelo'])) {
            $conModelo = $candidatos->filter(fn (EscaneoQr $e) => $e->coincideModelo($data['modelo']));
            if ($conModelo->isNotEmpty()) $candidatos = $conModelo;
        }

        // Ambiguo (varios QR distintos desde la misma IP): no adivinar.
        if ($candidatos->isEmpty() || $candidatos->pluck('codigo_qr_id')->unique()->count() > 1) {
            return response()->json(['codigo' => null, 'asesor' => null]);
        }

        /** @var EscaneoQr $escaneo */
        $escaneo = $candidatos->first();
        $escaneo->update(['atribuido_at' => now(), 'atribucion_tipo' => 'huella']);

        return response()->json([
            'codigo' => $escaneo->codigoQr->codigo,
            'asesor' => [
                'id'     => $escaneo->codigoQr->asesor_id,
                'nombre' => $escaneo->codigoQr->asesor?->name,
            ],
        ]);
    }
}
