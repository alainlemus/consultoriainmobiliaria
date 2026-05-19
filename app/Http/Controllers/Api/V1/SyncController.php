<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use App\Models\Expediente;
use App\Models\Ubicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    /**
     * POST /api/v1/sync
     * Procesa un lote de operaciones generadas offline en la app.
     *
     * Body: { "operaciones": [ { id_local, tipo, datos, timestamp } ] }
     */
    public function batch(Request $request): JsonResponse
    {
        $request->validate([
            'operaciones'              => ['required', 'array', 'min:1', 'max:100'],
            'operaciones.*.id_local'   => ['required', 'string'],
            'operaciones.*.tipo'       => ['required', 'string'],
            'operaciones.*.datos'      => ['nullable', 'array'],
            'operaciones.*.timestamp'  => ['nullable', 'string'],
        ]);

        $user       = $request->user();
        $resultados = [];

        foreach ($request->operaciones as $op) {
            $resultados[] = $this->procesarOperacion($user, $op);
        }

        return response()->json(['resultados' => $resultados]);
    }

    private function procesarOperacion($user, array $op): array
    {
        $idLocal = $op['id_local'];
        $tipo    = $op['tipo'];
        $datos   = $op['datos'] ?? [];

        try {
            $idServidor = match ($tipo) {
                'crear_contacto'      => $this->crearContacto($user, $datos),
                'actualizar_contacto' => $this->actualizarContacto($user, $datos),
                'crear_expediente'    => $this->crearExpediente($user, $datos),
                'actualizar_expediente' => $this->actualizarExpediente($user, $datos),
                'registrar_ubicacion' => $this->registrarUbicacion($user, $datos),
                default               => throw new \InvalidArgumentException("Tipo desconocido: {$tipo}"),
            };

            return [
                'id_local'    => $idLocal,
                'estado'      => 'ok',
                'id_servidor' => $idServidor,
            ];
        } catch (\Throwable $e) {
            return [
                'id_local' => $idLocal,
                'estado'   => 'error',
                'mensaje'  => $e->getMessage(),
            ];
        }
    }

    private function crearContacto($user, array $datos): int
    {
        $contacto = Contacto::create([
            'nombre'           => $datos['nombre']           ?? '',
            'apellido_paterno' => $datos['apellido_paterno'] ?? '',
            'apellido_materno' => $datos['apellido_materno'] ?? null,
            'telefono'         => $datos['telefono']         ?? null,
            'email'            => $datos['email']            ?? null,
            'estado_prospecto' => $datos['estado_prospecto'] ?? 'nuevo',
            'notas'            => $datos['notas']            ?? null,
            'asesor_id'        => $user->id,
        ]);
        return $contacto->id;
    }

    private function actualizarContacto($user, array $datos): int
    {
        $contacto = Contacto::findOrFail($datos['id']);
        if (! $user->hasRole('super_admin') && $contacto->asesor_id !== $user->id) {
            throw new \RuntimeException('Sin permisos.');
        }
        $contacto->update(collect($datos)->except(['id', 'asesor_id'])->toArray());
        return $contacto->id;
    }

    private function crearExpediente($user, array $datos): int
    {
        $exp = Expediente::create([
            'contacto_id'     => $datos['contacto_id'],
            'tipo_tramite_id' => $datos['tipo_tramite_id'],
            'estado'          => $datos['estado'] ?? 'en_proceso',
            'monto_credito'   => $datos['monto_credito']   ?? null,
            'notas_internas'  => $datos['notas']           ?? null,
            'asesor_id'       => $user->id,
        ]);
        return $exp->id;
    }

    private function actualizarExpediente($user, array $datos): int
    {
        $exp = Expediente::findOrFail($datos['id']);
        if (! $user->hasRole('super_admin') && $exp->asesor_id !== $user->id) {
            throw new \RuntimeException('Sin permisos.');
        }
        $exp->update(collect($datos)->except(['id', 'asesor_id'])->toArray());
        return $exp->id;
    }

    private function registrarUbicacion($user, array $datos): int
    {
        $ubicacion = Ubicacion::create([
            'user_id'     => $user->id,
            'contacto_id' => $datos['contacto_id'] ?? null,
            'latitud'     => $datos['latitud'],
            'longitud'    => $datos['longitud'],
            'tipo'        => $datos['tipo']        ?? 'visita_cliente',
            'notas'       => $datos['notas']       ?? null,
            'visitado_en' => $datos['visitado_en'] ?? now(),
        ]);
        return $ubicacion->id;
    }
}
