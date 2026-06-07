<?php

namespace App\Services;

use App\Models\Contacto;
use App\Models\ChatbotPaso;
use App\Models\Expediente;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Services\UmaService;
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
        if ($paso->clave === 'estado_ubicacion') {
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

        // Paso situacion_laboral: selección condicional (solo INFONAVIT/FOVISSSTE)
        } elseif ($paso->clave === 'situacion_laboral') {
            $opciones = collect($paso->opciones ?? []);
            $opcion   = $opciones->firstWhere('valor', trim($mensaje));
            if (! $opcion) {
                $texto = $this->interpolar($paso->mensaje, $conv);
                $this->whatsapp->sendText($conv->chat_id, $texto);
                $conv->save();
                return;
            }
            $conv->setDato('situacion_laboral', $opcion['etiqueta']);

        // Paso sueldo_precal: si omite, saltar TODA la precalificación
        } elseif ($paso->clave === 'sueldo_precal' && $omitir) {
            $conv->setDato('sueldo_precal', null);
            $conv->save();
            $pasoMensaje = ChatbotPaso::porClave('mensaje_libre');
            if ($pasoMensaje) {
                $this->ejecutarPaso($pasoMensaje, $conv);
            } else {
                $this->avanzarAlSiguiente($conv, $paso);
            }
            return;

        // Paso sueldo_precal: validar número > 1000
        } elseif ($paso->clave === 'sueldo_precal' && ! $omitir) {
            $num = (float) preg_replace('/[^0-9.]/', '', $mensaje);
            if ($num < 1000) {
                $this->whatsapp->sendText($conv->chat_id, "Por favor escribe tu sueldo mensual neto en números. Ejemplo: *15000*");
                $conv->save();
                return;
            }
            $conv->setDato('sueldo_precal', $num);

        // Paso edad_precal: validar 18–74
        } elseif ($paso->clave === 'edad_precal' && ! $omitir) {
            $num = (int) preg_replace('/[^0-9]/', '', $mensaje);
            if ($num < 18 || $num > 74) {
                $this->whatsapp->sendText($conv->chat_id, "Por favor escribe tu edad en años (entre 18 y 74). Ejemplo: *35*");
                $conv->save();
                return;
            }
            $conv->setDato('edad_precal', $num);

        // Paso antiguedad_precal: validar >= 1
        } elseif ($paso->clave === 'antiguedad_precal' && ! $omitir) {
            $num = (int) preg_replace('/[^0-9]/', '', $mensaje);
            if ($num < 1 || $num > 50) {
                $this->whatsapp->sendText($conv->chat_id, "Por favor escribe tus años de antigüedad laboral (mínimo 1). Ejemplo: *5*");
                $conv->save();
                return;
            }
            $conv->setDato('antiguedad_precal', $num);

        // Paso subcuenta_precal: número opcional (0 si omite o no es número)
        } elseif ($paso->clave === 'subcuenta_precal') {
            if ($omitir) {
                $conv->setDato('subcuenta_precal', 0);
            } else {
                $num = (float) preg_replace('/[^0-9.]/', '', $mensaje);
                $conv->setDato('subcuenta_precal', max(0, $num));
            }

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
        // Tras recopilar todos los datos de precalificación → enviar estimado
        if ($pasoActual?->clave === 'subcuenta_precal') {
            $this->enviarEstimadoPrecalificacion($conv);
        }

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

        // Situación laboral y precalificación: solo para servicios de crédito (INFONAVIT/FOVISSSTE)
        if (in_array($paso->clave, ['situacion_laboral', 'sueldo_precal', 'edad_precal', 'antiguedad_precal', 'subcuenta_precal'])) {
            return (bool) $conv->getDato('requiere_curp', false);
        }

        return true;
    }

    // ──────────────────────────────────────────────
    // PRECALIFICACIÓN — estimado vía WhatsApp
    // ──────────────────────────────────────────────

    private function enviarEstimadoPrecalificacion(WhatsappConversation $conv): void
    {
        $datos      = $conv->datos ?? [];
        $sueldo     = (float) ($datos['sueldo_precal']     ?? 0);
        $edad       = (int)   ($datos['edad_precal']       ?? 0);
        $antiguedad = (int)   ($datos['antiguedad_precal'] ?? 0);
        $subcuenta  = (float) ($datos['subcuenta_precal']  ?? 0);
        $situacion  = $datos['situacion_laboral'] ?? '';

        // Si faltan datos clave, no calcular
        if ($sueldo < 1000 || $edad < 18 || $antiguedad < 1) {
            Log::info('[Chatbot] Precal: datos insuficientes, se omite estimado.');
            return;
        }

        $uma         = UmaService::getUmaMensual(); // UMA mensual desde BD
        $primerNombre = explode(' ', $datos['nombre'] ?? 'Estimado')[0];

        // Determinar producto y parámetros según situación laboral
        if (str_contains($situacion, 'ISSSTE') || str_contains($situacion, 'FOVISSSTE')) {
            // ── Crédito Tradicional FOVISSSTE ─────────────────────────────
            $producto    = 'Crédito Tradicional FOVISSSTE';
            $umasTrab    = $uma > 0 ? $sueldo / $uma : 0;
            $tasa        = $umasTrab <= 4 ? 0.04 : ($umasTrab <= 7 ? 0.05 : 0.06);
            $plazo       = max(0, min(30, 65 - $edad));
            $topeCredito = 954 * $uma; // tope en UMAs

        } elseif (str_contains($situacion, 'IMSS') || str_contains($situacion, 'INFONAVIT')) {
            // ── INFONAVIT (sector privado) ─────────────────────────────────
            $producto    = 'Crédito INFONAVIT';
            $tasa        = 0.10; // tasa orientativa — varía según puntos y salario
            $plazo       = max(0, min(30, 65 - $edad));
            $topeCredito = null; // sin tope UMA (INFONAVIT no usa UMA como tope)

        } else {
            // ── Independiente → solo orientativo bancario ──────────────────
            $producto    = 'Crédito bancario (orientativo)';
            $tasa        = 0.12;
            $plazo       = max(0, min(20, 65 - $edad));
            $topeCredito = null;
        }

        // Verificar elegibilidad mínima
        if ($plazo < 3) {
            $conv->setDato('resultado_precalificacion', 'no_califica');
            $conv->save();
            $this->whatsapp->sendText(
                $conv->chat_id,
                "📊 *Estimado de precalificación*\n\n" .
                "⚠️ Con {$edad} años el plazo disponible sería muy corto ({$plazo} años).\n\n" .
                "Un asesor evaluará opciones adicionales para tu caso. 🏠\n\n" .
                "_Consultoría Inmobiliaria_"
            );
            return;
        }

        // Cálculo de monto por capacidad de pago
        $pagoMax     = $sueldo * 0.30;
        $tasaMensual = $tasa / 12;
        $n           = $plazo * 12;
        $montoCapacidad = ($n > 0 && $tasaMensual > 0)
            ? $pagoMax * (1 - pow(1 + $tasaMensual, -$n)) / $tasaMensual
            : 0;

        $monto = ($topeCredito !== null) ? min($montoCapacidad, $topeCredito) : $montoCapacidad;
        $valorInmueble = $monto + $subcuenta;
        $mensualidad   = ($tasaMensual > 0 && $n > 0)
            ? $monto * $tasaMensual / (1 - pow(1 + $tasaMensual, -$n))
            : 0;

        if ($monto < 100000) {
            $conv->setDato('resultado_precalificacion', 'no_califica');
            $conv->save();
            $this->whatsapp->sendText(
                $conv->chat_id,
                "📊 *Estimado de precalificación*\n\n" .
                "Con un sueldo de $" . number_format($sueldo, 0, '.', ',') . " el monto estimado es bajo.\n\n" .
                "⚠️ Te recomendamos hablar con un asesor para explorar todas las opciones disponibles. 🏠\n\n" .
                "_Consultoría Inmobiliaria_"
            );
            return;
        }

        $conv->setDato('resultado_precalificacion', 'pre_califica');
        $conv->save();

        $msg  = "📊 *Estimado de precalificación*\n\n";
        $msg .= "Basado en tu información:\n";
        $msg .= "• Sueldo: *$" . number_format($sueldo, 0, '.', ',') . "/mes*\n";
        $msg .= "• Edad: {$edad} años · Antigüedad: {$antiguedad} años\n";
        if ($subcuenta > 0) {
            $msg .= "• Subcuenta: $" . number_format($subcuenta, 0, '.', ',') . "\n";
        }
        $msg .= "\n✅ *Podrías calificar aproximadamente para:*\n\n";
        $msg .= "💰 Crédito: *$" . number_format(round($monto, -3), 0, '.', ',') . " MXN*\n";
        if ($subcuenta > 0) {
            $msg .= "🏡 Valor del inmueble: *$" . number_format(round($valorInmueble, -3), 0, '.', ',') . " MXN*\n";
        }
        $msg .= "📅 Plazo: {$plazo} años\n";
        $msg .= "💳 Mensualidad aprox.: *$" . number_format(round($mensualidad, -1), 0, '.', ',') . "/mes*\n";
        $msg .= "📌 Producto: {$producto}\n";
        $msg .= "\n_⚠️ Este es un estimado *orientativo*. El monto real depende de tu expediente y puntos._\n";
        $msg .= "_Un asesor te dará los detalles exactos. 🏠_\n\n";
        $msg .= "_Consultoría Inmobiliaria_";

        $this->whatsapp->sendText($conv->chat_id, $msg);

        Log::info("[Chatbot] Estimado precal enviado a {$conv->chat_id}: monto={$monto}, producto={$producto}");
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
        if (isset($datos['sueldo_precal'])) {
            $notas .= "Sueldo declarado: $" . number_format((float)$datos['sueldo_precal'], 0, '.', ',') . "\n";
        }
        if (isset($datos['edad_precal'])) {
            $notas .= "Edad: " . $datos['edad_precal'] . " años\n";
        }
        if (isset($datos['antiguedad_precal'])) {
            $notas .= "Antigüedad: " . $datos['antiguedad_precal'] . " años\n";
        }
        foreach ($datos as $clave => $valor) {
            if (! in_array($clave, ['nombre', 'nombre_completo', 'servicio', 'servicio_clave', 'correo',
                                     'requiere_curp', 'telefono_confirmado', 'sueldo_precal',
                                     'edad_precal', 'antiguedad_precal', 'subcuenta_precal']) && $valor) {
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
                'nombre'                  => $nombreCompleto,
                'email'                   => $datos['correo'] ?? $contactoExistente->email,
                'estado_prospecto'        => 'nuevo',
                'estado_ubicacion'        => $datos['estado_ubicacion'] ?? $contactoExistente->estado_ubicacion,
                'tipo_credito_interes'    => $tipoCredito ?? $contactoExistente->tipo_credito_interes,
                'mensaje'                 => $datos['mensaje_libre'] ?? $contactoExistente->mensaje,
                'curp'                    => isset($datos['curp']) ? strtoupper($datos['curp']) : $contactoExistente->curp,
                'resultado_precalificacion' => $datos['resultado_precalificacion'] ?? $contactoExistente->resultado_precalificacion,
                'notas'                   => $notas,
            ]);
            Log::info("[Chatbot WhatsApp] Contacto actualizado: {$telefono} — {$nombreCompleto}");
        } else {
            // Crear nuevo contacto
            Contacto::create([
                'nombre'                    => $nombreCompleto,
                'telefono'                  => $telefono,
                'email'                     => $datos['correo'] ?? null,
                'origen'                    => 'whatsapp',
                'estado_prospecto'          => 'nuevo',
                'estado_ubicacion'          => $datos['estado_ubicacion'] ?? null,
                'tipo_credito_interes'      => $tipoCredito,
                'mensaje'                   => $datos['mensaje_libre'] ?? null,
                'curp'                      => isset($datos['curp']) ? strtoupper($datos['curp']) : null,
                'resultado_precalificacion' => $datos['resultado_precalificacion'] ?? null,
                'fecha_primer_contacto'     => now()->toDateString(),
                'notas'                     => $notas,
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

        $this->notificarSuperAdmin($datos, $nombreCompleto, $telefono);
    }

    // ──────────────────────────────────────────────
    // NOTIFICACIÓN AL SUPER ADMIN
    // ──────────────────────────────────────────────

    private function notificarSuperAdmin(array $datos, string $nombreCompleto, string $telefono): void
    {
        try {
            // Buscar todos los usuarios super_admin con teléfono registrado
            $admins = User::role('super_admin')->whereNotNull('telefono')->where('telefono', '!=', '')->get();

            if ($admins->isEmpty()) {
                Log::info('[Chatbot] notificarSuperAdmin: ningún super_admin con teléfono registrado.');
                return;
            }

            $msg = $this->construirMensajeAdmin($datos, $nombreCompleto, $telefono);

            foreach ($admins as $admin) {
                $chatId = preg_replace('/\D/', '', $admin->telefono) . '@c.us';
                $this->whatsapp->sendText($chatId, $msg);
                Log::info("[Chatbot] Notificación enviada a super_admin {$admin->name} ({$admin->telefono})");
            }

        } catch (\Throwable $e) {
            // No interrumpir el flujo si falla la notificación
            Log::warning('[Chatbot] Error al notificar super_admin: ' . $e->getMessage());
        }
    }

    private function construirMensajeAdmin(array $datos, string $nombreCompleto, string $telefono): string
    {
        $servicio   = $datos['servicio']          ?? '—';
        $estado     = $datos['estado_ubicacion']  ?? '—';
        $situacion  = $datos['situacion_laboral'] ?? '—';
        $correo     = $datos['correo']            ?? '—';
        $curp       = isset($datos['curp'])       ? strtoupper($datos['curp']) : null;
        $mensaje    = $datos['mensaje_libre']     ?? null;
        $resultado  = $datos['resultado_precalificacion'] ?? null;

        // Datos de precalificación
        $sueldo     = isset($datos['sueldo_precal'])     ? '$' . number_format((float)$datos['sueldo_precal'], 0, '.', ',') : null;
        $edad       = isset($datos['edad_precal'])       ? $datos['edad_precal'] . ' años' : null;
        $antiguedad = isset($datos['antiguedad_precal']) ? $datos['antiguedad_precal'] . ' años' : null;
        $subcuenta  = isset($datos['subcuenta_precal']) && (float)$datos['subcuenta_precal'] > 0
                        ? '$' . number_format((float)$datos['subcuenta_precal'], 0, '.', ',')
                        : null;

        $resultadoEmoji = match ($resultado) {
            'pre_califica' => '✅ Pre-califica',
            'no_califica'  => '❌ No califica',
            default        => null,
        };

        // ── Construir mensaje ──────────────────────────────────────────────
        $msg  = "🔔 *Nuevo prospecto registrado*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "👤 *Nombre:* {$nombreCompleto}\n";
        $msg .= "📱 *Teléfono:* {$telefono}\n";

        if ($correo && $correo !== '—') {
            $msg .= "📧 *Correo:* {$correo}\n";
        }

        $msg .= "📍 *Estado:* {$estado}\n";
        $msg .= "🏦 *Servicio:* {$servicio}\n";
        $msg .= "💼 *Situación laboral:* {$situacion}\n";

        // Bloque de precalificación (solo si hizo el flujo)
        if ($sueldo || $edad || $antiguedad) {
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "📊 *Datos de precalificación:*\n";
            if ($sueldo)     $msg .= "  • Sueldo mensual: {$sueldo}\n";
            if ($edad)       $msg .= "  • Edad: {$edad}\n";
            if ($antiguedad) $msg .= "  • Antigüedad: {$antiguedad}\n";
            if ($subcuenta)  $msg .= "  • Subcuenta vivienda: {$subcuenta}\n";
            if ($resultadoEmoji) {
                $msg .= "  • Resultado: *{$resultadoEmoji}*\n";
            }
        }

        // CURP si la proporcionó
        if ($curp) {
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "🪪 *CURP:* {$curp}\n";
        }

        // Mensaje libre si lo escribió
        if ($mensaje) {
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "💬 *Mensaje del prospecto:*\n_{$mensaje}_\n";
        }

        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "🕐 " . now()->timezone('America/Mexico_City')->format('d/m/Y H:i') . " hrs\n";
        $msg .= "_Consultoría Inmobiliaria — CRM_";

        return $msg;
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
