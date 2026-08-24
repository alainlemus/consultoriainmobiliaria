<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * POST /api/v1/dispositivos (asesor/admin) y POST /api/v1/acreditado/dispositivos
     * Registra o actualiza el FCM token del dispositivo actual.
     *
     * $request->user() puede ser un User (asesor/admin) o un Acreditado según
     * el guard/token con el que se autenticó — DeviceToken es polimórfico
     * (tokenable_type/tokenable_id) para soportar ambos. 'user_id' se rellena
     * solo cuando es un User, por compatibilidad con PushService y consultas
     * existentes que aún lo usan.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token'  => ['required', 'string'],
            'plataforma' => ['required', 'in:ios,android'],
        ]);

        $notifiable = $request->user();
        $esUser     = $notifiable instanceof \App\Models\User;

        DeviceToken::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            [
                'user_id'        => $esUser ? $notifiable->id : null,
                'tokenable_type' => get_class($notifiable),
                'tokenable_id'   => $notifiable->id,
                'plataforma'     => $data['plataforma'],
                'ultimo_uso'     => now(),
            ]
        );

        return response()->json(['message' => 'Dispositivo registrado.'], 201);
    }
}
