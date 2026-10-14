<?php

namespace App\Services;

use App\Models\Contacto;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\User;

/**
 * Inicia el expediente de un prospecto. Lo usan la API normal y la cola de sincronización
 * (cuando el asesor lo inició sin señal), para que se comporten igual: un prospecto tiene un
 * solo expediente abierto y el expediente guarda los datos del prospecto para ligarlo con su
 * cuenta de acreditado (correo/CURP).
 */
class ExpedienteInicioService
{
    /** @return array{0: Expediente, 1: bool} el expediente y si se creó ahora (false = ya existía) */
    public function iniciar(User $asesor, array $data): array
    {
        $abierto = Expediente::where('contacto_id', $data['contacto_id'])
            ->whereNotIn('estado', ['cerrado', 'cancelado'])
            ->latest()
            ->first();

        if ($abierto) {
            return [$abierto, false];
        }

        $contacto = Contacto::find($data['contacto_id']);

        $exp = Expediente::create([
            ...$data,
            'acreditado_email'    => $contacto?->email,
            'acreditado_curp'     => $contacto?->curp,
            'acreditado_telefono' => $contacto?->telefono,
            'asesor_id'           => $asesor->id,
            'estado'              => $data['estado'] ?? 'en_proceso',
            'etapa_tramite_id'    => $data['etapa_tramite_id']
                ?? EtapaTramite::where('tipo_tramite_id', $data['tipo_tramite_id'])->orderBy('orden')->value('id'),
            'acreditado_nombre'   => $data['acreditado_nombre'] ?? $contacto?->nombre,
        ]);

        return [$exp, true];
    }
}
