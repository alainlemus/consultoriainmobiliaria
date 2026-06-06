<?php

namespace App\Services;

use App\Models\Contacto;
use App\Models\ChatbotPaso;
use App\Models\Expediente;
use App\Models\WhatsappConversation;
use Illuminate\Support\Facades\Log;

class WhatsappChatbotService
{
    public function __construct(
        private WhatsAppService $whatsapp
    ) {}

    /**
     * Punto de entrada: procesa un mensaje entrante y avanza el flujo.
     */
    public function procesar(string $chatId, string $telefono, ?string $mensaje, ?string $pushName = null): void
    {
        $conv = WhatsappConversation::firstOrNew(['chat_id' => $chatId]);

        // Conversación nueva o expirada → reiniciar desde el primer paso
        if (! $conv->exists || $conv->expirada()) {
            $primerPaso = ChatbotPaso::flujoActivo()->first();
            if (! $primerPaso) {
                Log::warning('[Chatbot] No hay pasos configurados en BD.');
                return;
            }

            $conv->chat_id           = $chatId;
            $conv->telefono          = $telefono;
            $conv->datos             = [];
            $conv->ultimo_mensaje_at = now();
            $conv->paso              = $primerPaso->clave;
            $conv->save();

            $this->ejecutarPaso($primerPaso, $conv, $pushName);
            return;
        }

        $conv->ultimo_mensaje_at = now();
        $mensaje = trim($mensaje ?? '');

        if ($conv->paso === 'completado') {
            $mensajeNormalizado = strtolower(trim($mensaje ?? ''));
            if (in_array($mensajeNormalizado, ['hola', 'hola!', 'hola.', 'hi', 'buenas', 'buenos días', 'buenas tardes', 'buenas noches'])) {
                // Reiniciar flujo
                $conv->datos             = [];
                $conv->ultimo_mensaje_at = now();
                $primerPaso = ChatbotPaso::flujoActivo()->first();
                if (! $primerPaso) return;
                $conv->paso = $primerPaso->clave;
                $conv->save();
                $this->ejecutarPaso($primerPaso, $conv, $pushName);
                return;
            }
            $this->mensajeYaRegistrado($chatId);
            return;
        }

        $pasoActual = ChatbotPaso::porClave($conv->paso);

        if (! $pasoActual) {
            // Paso eliminado/desactivado — avanzar al siguiente activo
            $this->avanzarAlSiguiente($conv, null);
            return;
        }

        $this->procesarRespuesta($pasoActual, $conv, $mensaje);
    }

    // ──────────────────────────────────────────────
    // EJECUTAR UN PASO (enviar mensaje al usuario)
    // ──────────────────────────────────────────────

    private function ejecutarPaso(ChatbotPaso $paso, WhatsappConversation $conv, ?string $nombre = null): void
    {
        $texto = $this->interpolar($paso->mensaje, $conv, $nombre);
        $this->whatsapp->sendText($conv->chat_id, $texto);
        $conv->paso = $paso->clave;
        $conv->save();

        // Si es solo un mensaje (sin esperar respuesta), avanzar automáticamente
        if ($paso->tipo === 'mensaje') {
            $this->avanzarAlSiguiente($conv, $paso);
        }
    }

    // ──────────────────────────────────────────────
    // PROCESAR RESPUESTA DEL USUARIO
    // ──────────────────────────────────────────────

    private function procesarRespuesta(ChatbotPaso $paso, WhatsappConversation $conv, string $mensaje): void
    {
        // Permitir "omitir" en pasos no requeridos
        $omitir = ! $paso->requerido && strtolower($mensaje) === 'omitir';

        match ($paso->tipo) {
            'seleccion'   => $this->procesarSeleccion($paso, $conv, $mensaje),
            'texto_libre' => $this->procesarTextoLibre($paso, $conv, $mensaje, $omitir),
            'condicional' => $this->procesarCondicional($paso, $conv, $mensaje, $omitir),
            default       => $this->avanzarAlSiguiente($conv, null),
        };    }

    private function procesarSeleccion(ChatbotPaso $paso, WhatsappConversation $conv, string $mensaje): void
    {
        $opciones = collect($paso->opciones ?? []);
        $opcion   = $opciones->firstWhere('valor', trim($mensaje));

        if (! $opcion) {
            $texto = $this->interpolar($paso->mensaje, $conv);
            $this->whatsapp->sendText($conv->chat_id, $texto);
            $conv->save();
            return;
        }

        // Paso especial: confirmación de teléfono
        if ($paso->clave === 'confirmacion_telefono') {
            if (trim($mensaje) === '1') {
                // Confirma el teléfono detectado
                $conv->setDato('telefono_confirmado', $conv->telefono);
                $conv->save();
                $this->avanzarAlSiguiente($conv, $paso);
            } else {
                // Quiere cambiar → ir al paso telefono_manual
                $pasoManual = ChatbotPaso::porClave('telefono_manual');
                if ($pasoManual) {
                    $this->ejecutarPaso($pasoManual, $conv);
                } else {
                    $this->avanzarAlSiguiente($conv, $paso);
                }
            }
            return;
        }

        // Pasos de selección genéricos: guardar etiqueta con la clave del paso
        if (in_array($paso->clave, ['estado_ubicacion', 'situacion_laboral'])) {
            $conv->setDato($paso->clave, $opcion['etiqueta']);
            $conv->save();
            $this->avanzarAlSiguiente($conv, $paso);
            return;
        }

        $conv->setDato('servicio', $opcion['etiqueta']);
        $conv->setDato('servicio_clave', $opcion['valor']);
        $conv->setDato('requiere_curp', $opcion['requiere_curp'] ?? false);
        $conv->save();

        $this->avanzarAlSiguiente($conv, $paso);
    }

    private function procesarTextoLibre(ChatbotPaso $paso, WhatsappConversation $conv, string $mensaje, bool $omitir): void
    {
        if (! $omitir && strlen($mensaje) < 2) {
            $this->whatsapp->sendText($conv->chat_id, "Por favor escribe una respuesta válida o escribe *omitir*.");
            $conv->save();
            return;
        }

        // Validación especial para correo
        if ($paso->clave === 'correo' && ! $omitir && ! filter_var($mensaje, FILTER_VALIDATE_EMAIL)) {
            $this->whatsapp->sendText($conv->chat_id, "El correo no parece válido. Por favor escríbelo de nuevo o escribe *omitir*.");
            $conv->save();
            return;
        }

        // Validación especial para teléfono manual
        if ($paso->clave === 'telefono_manual') {
            $tel = preg_replace('/\D/', '', $mensaje);
            if (strlen($tel) !== 10) {
                $this->whatsapp->sendText($conv->chat_id, "El número debe tener exactamente *10 dígitos*. Por ejemplo: 5512345678\n\nIntenta de nuevo:");
                $conv->save();
                return;
            }
            $conv->setDato('telefono_confirmado', $tel);
            $conv->telefono = $tel;
            $conv->save();
            $this->avanzarAlSiguiente($conv, $paso);
            return;
        }

        $conv->setDato($paso->clave, $omitir ? null : $mensaje);
        $conv->save();

        $this->avanzarAlSiguiente($conv, $paso);
    }

    private function procesarCondicional(ChatbotPaso $paso, WhatsappConversation $conv, string $mensaje, bool $omitir): void
    {
        // Paso CURP: validar formato si no omite
        if ($paso->clave === 'curp' && ! $omitir) {
            $curp = strtoupper(trim($mensaje));
            if (! $this->validarCurp($curp)) {
                $this->whatsapp->sendText($conv->chat_id, "La CURP no parece válida (18 caracteres).\nIntenta de nuevo o escribe *omitir*.");
                $conv->save();
                return;
            }
            $conv->setDato('curp', $curp);
        } else {
            $conv->setDato($paso->clave, $omitir ? null : $mensaje);
        }

        $conv->save();
        $this->avanzarAlSiguiente($conv, $paso);
    }

    // ──────────────────────────────────────────────
    // NAVEGACIÓN ENTRE PASOS
    // ──────────────────────────────────────────────

    private function avanzarAlSiguiente(WhatsappConversation $conv, ?ChatbotPaso $pasoActual): void
    {
        $flujo = ChatbotPaso::flujoActivo();

        // Determinar el siguiente paso
        $siguienteClave = $pasoActual?->siguiente_paso;

        if ($siguienteClave) {
            $siguiente = $flujo->firstWhere('clave', $siguienteClave);
        } else {
            // Siguiente en orden
            $indiceActual = $pasoActual
                ? $flujo->search(fn ($p) => $p->clave === $pasoActual->clave)
                : -1;
            $siguiente = $flujo->slice($indiceActual + 1)->first();
        }

        // Saltar pasos condicionales si no aplican
        while ($siguiente && $siguiente->tipo === 'condicional') {
            if (! $this->aplicaCondicional($siguiente, $conv)) {
                $siguiente = $flujo->slice(
                    $flujo->search(fn ($p) => $p->clave === $siguiente->clave) + 1
                )->first();
            } else {
                break;
            }
        }

        if (! $siguiente) {
            // Fin del flujo → crear prospecto
            $this->crearProspecto($conv);
            return;
        }

        $this->ejecutarPaso($siguiente, $conv);
    }

    private function aplicaCondicional(ChatbotPaso $paso, WhatsappConversation $conv): bool
    {
        // El paso CURP solo aplica si el servicio requiere CURP
        if ($paso->clave === 'curp') {
            return (bool) $conv->getDato('requiere_curp', false);
        }

        return true;
    }

    // ──────────────────────────────────────────────
    // CREAR PROSPECTO AL FINAL DEL FLUJO
    // ──────────────────────────────────────────────

    private function crearProspecto(WhatsappConversation $conv): void
    {
        $datos          = $conv->datos ?? [];
        $nombreCompleto = $datos['nombre'] ?? $datos['nombre_completo'] ?? 'Prospecto WhatsApp';
        $primerNombre   = explode(' ', $nombreCompleto)[0];
        $telefono       = $datos['telefono_confirmado'] ?? $conv->telefono;

        // Mapear situacion_laboral → tipo_credito_interes (valores del Select de Filament)
        $mapaTipoCredito = [
            'Trabajador IMSS (INFONAVIT)'   => 'infonavit',
            'Trabajador ISSSTE (FOVISSSTE)'  => 'fovissste',
            'Independiente / otro'           => 'otro',
        ];
        $tipoCredito = $mapaTipoCredito[$datos['situacion_laboral'] ?? ''] ?? null;

        $notas = "Prospecto generado por chatbot WhatsApp.\n";
        $notas .= "Servicio de interés: " . ($datos['servicio'] ?? '—') . "\n";
        foreach ($datos as $clave => $valor) {
            if (! in_array($clave, ['nombre', 'nombre_completo', 'servicio', 'servicio_clave', 'correo', 'requiere_curp', 'telefono_confirmado']) && $valor) {
                $notas .= ucfirst($clave) . ": {$valor}\n";
            }
        }

        $conv->paso = 'completado';
        $conv->save();

        // Verificar si tiene expediente activo
        $contactoExistente = Contacto::where('telefono', $telefono)->first();
        if ($contactoExistente) {
            $tieneExpediente = Expediente::where('contacto_id', $contactoExistente->id)->exists();
            if ($tieneExpediente) {
                $this->whatsapp->sendText(
                    $conv->chat_id,
                    "ℹ️ *{$primerNombre}*, ya tienes un expediente activo con nosotros.\n\n" .
                    "Un asesor está trabajando en tu caso y se pondrá en contacto contigo pronto. 🏠\n\n" .
                    "_Consultoría Inmobiliaria_"
                );
                Log::info("[Chatbot WhatsApp] Expediente existente para: {$telefono}");
                return;
            }

            // Actualizar datos del contacto existente
            $contactoExistente->update([
                'nombre'              => $nombreCompleto,
                'email'               => $datos['correo'] ?? $contactoExistente->email,
                'estado_prospecto'    => 'nuevo',
                'estado_ubicacion'    => $datos['estado_ubicacion'] ?? $contactoExistente->estado_ubicacion,
                'tipo_credito_interes'=> $tipoCredito ?? $contactoExistente->tipo_credito_interes,
                'mensaje'             => $datos['mensaje_libre'] ?? $contactoExistente->mensaje,
                'curp'                => isset($datos['curp']) ? strtoupper($datos['curp']) : $contactoExistente->curp,
                'notas'               => $notas,
            ]);
            Log::info("[Chatbot WhatsApp] Contacto actualizado: {$telefono} — {$nombreCompleto}");
        } else {
            // Crear nuevo contacto
            Contacto::create([
                'nombre'                => $nombreCompleto,
                'telefono'              => $telefono,
                'email'                 => $datos['correo'] ?? null,
                'origen'                => 'whatsapp',
                'estado_prospecto'      => 'nuevo',
                'estado_ubicacion'      => $datos['estado_ubicacion'] ?? null,
                'tipo_credito_interes'  => $tipoCredito,
                'mensaje'               => $datos['mensaje_libre'] ?? null,
                'curp'                  => isset($datos['curp']) ? strtoupper($datos['curp']) : null,
                'fecha_primer_contacto' => now()->toDateString(),
                'notas'                 => $notas,
            ]);
            Log::info("[Chatbot WhatsApp] Prospecto creado: {$telefono} — {$nombreCompleto}");
        }

        $this->whatsapp->sendText(
            $conv->chat_id,
            "✅ ¡Listo *{$primerNombre}*!\n\n" .
            "Hemos registrado tu solicitud de *" . ($datos['servicio'] ?? 'nuestros servicios') . "*.\n\n" .
            "Un asesor se pondrá en contacto contigo a la brevedad. 🏠\n\n" .
            "_Consultoría Inmobiliaria_"
        );
    }

    private function mensajeYaRegistrado(string $chatId): void
    {
        $this->whatsapp->sendText(
            $chatId,
            "Ya tienes una solicitud registrada. Un asesor se pondrá en contacto contigo pronto. 🏠\n\n" .
            "Si deseas iniciar una nueva consulta, escribe *hola*."
        );
    }

    // ──────────────────────────────────────────────
    // INTERPOLACIÓN DE VARIABLES EN MENSAJES
    // ──────────────────────────────────────────────

    private function interpolar(string $texto, WhatsappConversation $conv, ?string $pushName = null): string
    {
        $datos    = $conv->datos ?? [];
        $nombre   = $pushName ?? $datos['nombre'] ?? $datos['nombre_completo'] ?? '';
        $servicio = $datos['servicio'] ?? '';
        $telefono = $datos['telefono_confirmado'] ?? $conv->telefono ?? '';

        // {menu} → genera la lista numerada del paso de selección
        if (str_contains($texto, '{menu}')) {
            $pasoServicio = ChatbotPaso::porClave('servicio');
            $menu = '';
            if ($pasoServicio && $pasoServicio->opciones) {
                foreach ($pasoServicio->opciones as $op) {
                    $emoji = ['1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣'][$op['valor'] - 1] ?? $op['valor'];
                    $menu .= "{$emoji}  {$op['etiqueta']}\n";
                }
            }
            $texto = str_replace('{menu}', trim($menu), $texto);
        }

        $total = count(ChatbotPaso::porClave('servicio')?->opciones ?? []);

        return str_replace(
            ['{nombre}', '{servicio}', '{total}', '{telefono}'],
            [$nombre, $servicio, $total, $telefono],
            $texto
        );
    }

    // ──────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────

    private function validarCurp(string $curp): bool
    {
        return (bool) preg_match(
            '/^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|[12][0-9]|3[01])[HM]{1}(AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/',
            $curp
        );
    }
}
