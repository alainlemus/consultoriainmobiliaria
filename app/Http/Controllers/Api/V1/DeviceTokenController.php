<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * POST /api/v1/dispositivos
     * Registra o actualiza el FCM token del dispositivo actual.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token'  => ['required', 'string'],
            'plataforma' => ['required', 'in:ios,android'],
        ]);

        DeviceToken::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            [
                'user_id'    => $request->user()->id,
                'plataforma' => $data['plataforma'],
                'ultimo_uso' => now(),
            ]
        );

        return response()->json(['message' => 'Dispositivo registrado.'], 201);
    }
}
