<?php

namespace App\Services;

use App\Models\Contacto;
use App\Models\WhatsappConversation;
use Illuminate\Support\Facades\Log;

class WhatsappChatbotService
{
    // Servicios disponibles
    const SERVICIOS = [
        '1' => 'Crédito INFONAVIT',
        '2' => 'Crédito FOVISSSTE',
        '3' => 'Avalúo',
        '4' => 'Escrituración',
        '5' => 'Asesoría personalizada',
    ];

    // Servicios que requieren CURP para simulación
    const SERVICIOS_CON_CURP = ['1', '2'];

    public function __construct(
        private WhatsAppService $whatsapp
    ) {}

    /**
     * Procesar un mensaje entrante y avanzar el flujo del chatbot.
     */
    public function procesar(string $chatId, string $telefono, ?string $mensaje, ?string $pushName = null): void
    {
        $conv = WhatsappConversation::firstOrNew(['chat_id' => $chatId]);

        // Si es nueva o expiró, reiniciar
        if (! $conv->exists || $conv->expirada()) {
            $conv->chat_id           = $chatId;
            $conv->telefono          = $telefono;
            $conv->paso              = 'inicio';
            $conv->datos             = [];
            $conv->ultimo_mensaje_at = now();
            $conv->save();

            $this->enviarBienvenida($chatId, $pushName);
            $conv->paso = 'esperando_servicio';
            $conv->ultimo_mensaje_at = now();
            $conv->save();
            return;
        }

        $conv->ultimo_mensaje_at = now();
        $mensaje = trim($mensaje ?? '');

        match ($conv->paso) {
            'esperando_servicio' => $this->procesarServicio($conv, $mensaje),
            'esperando_nombre'   => $this->procesarNombre($conv, $mensaje),
            'esperando_correo'   => $this->procesarCorreo($conv, $mensaje),
            'esperando_curp'     => $this->procesarCurp($conv, $mensaje),
            'completado'         => $this->mensajeYaRegistrado($chatId),
            default              => $this->enviarBienvenida($chatId, $pushName),
        };
    }

    // ──────────────────────────────────────────────
    // PASOS
    // ──────────────────────────────────────────────

    private function enviarBienvenida(string $chatId, ?string $nombre = null): void
    {
        $saludo = $nombre ? "Hola {$nombre} 👋" : "Hola 👋";

        $this->whatsapp->sendText(
            $chatId,
            "{$saludo} Bienvenido a *Consultoría Inmobiliaria*.\n\n" .
            "¿En qué podemos ayudarte? Responde con el número de tu opción:\n\n" .
            "1️⃣  Crédito INFONAVIT\n" .
            "2️⃣  Crédito FOVISSSTE\n" .
            "3️⃣  Avalúo\n" .
            "4️⃣  Escrituración\n" .
            "5️⃣  Asesoría personalizada"
        );
    }

    private function procesarServicio(WhatsappConversation $conv, string $mensaje): void
    {
        $opcion = trim($mensaje);

        if (! isset(self::SERVICIOS[$opcion])) {
            $this->whatsapp->sendText(
                $conv->chat_id,
                "Por favor responde con un número del *1 al 5* para seleccionar tu servicio. 😊"
            );
            $conv->save();
            return;
        }

        $conv->setDato('servicio', self::SERVICIOS[$opcion]);
        $conv->setDato('servicio_clave', $opcion);
        $conv->paso = 'esperando_nombre';
        $conv->save();

        $this->whatsapp->sendText(
            $conv->chat_id,
            "Excelente, seleccionaste *" . self::SERVICIOS[$opcion] . "* ✅\n\n¿Cuál es tu *nombre completo*?"
        );
    }

    private function procesarNombre(WhatsappConversation $conv, string $mensaje): void
    {
        if (strlen($mensaje) < 3) {
            $this->whatsapp->sendText($conv->chat_id, "Por favor escribe tu nombre completo.");
            $conv->save();
            return;
        }

        $conv->setDato('nombre_completo', $mensaje);
        $conv->paso = 'esperando_correo';
        $conv->save();

        $this->whatsapp->sendText(
            $conv->chat_id,
            "Gracias *{$mensaje}* 😊\n\n¿Cuál es tu *correo electrónico*?\n_(Escribe 'omitir' si no deseas proporcionarlo)_"
        );
    }

    private function procesarCorreo(WhatsappConversation $conv, string $mensaje): void
    {
        $omitir = strtolower($mensaje) === 'omitir';

        if (! $omitir && ! filter_var($mensaje, FILTER_VALIDATE_EMAIL)) {
            $this->whatsapp->sendText(
                $conv->chat_id,
                "El correo no parece válido. Por favor escríbelo de nuevo o escribe *omitir*."
            );
            $conv->save();
            return;
        }

        $conv->setDato('correo', $omitir ? null : $mensaje);

        // ¿Requiere CURP?
        $clave = $conv->getDato('servicio_clave');
        if (in_array($clave, self::SERVICIOS_CON_CURP)) {
            $conv->paso = 'esperando_curp';
            $conv->save();

            $this->whatsapp->sendText(
                $conv->chat_id,
                "Para realizar una simulación de crédito necesitamos tu *CURP*.\n\n" .
                "Por favor escríbela (18 caracteres):\n_(Escribe 'omitir' para continuar sin ella)_"
            );
        } else {
            $this->crearProspecto($conv);
        }
    }

    private function procesarCurp(WhatsappConversation $conv, string $mensaje): void
    {
        $omitir = strtolower($mensaje) === 'omitir';
        $curp   = strtoupper(trim($mensaje));

        if (! $omitir && ! $this->validarCurp($curp)) {
            $this->whatsapp->sendText(
                $conv->chat_id,
                "La CURP no parece válida (debe tener 18 caracteres alfanuméricos).\n" .
                "Intenta de nuevo o escribe *omitir*."
            );
            $conv->save();
            return;
        }

        $conv->setDato('curp', $omitir ? null : $curp);
        $this->crearProspecto($conv);
    }

    // ──────────────────────────────────────────────
    // CREAR PROSPECTO
    // ──────────────────────────────────────────────

    /**
     * Crear prospecto al finalizar el flujo completo del chatbot.
     */
    private function crearProspecto(WhatsappConversation $conv): void
    {
        $datos          = $conv->datos ?? [];
        $nombreCompleto = $datos['nombre_completo'] ?? 'Prospecto WhatsApp';
        $partes         = explode(' ', $nombreCompleto, 2);
        $nombre         = $partes[0];
        $apellidos      = $partes[1] ?? '';

        $notas = "Prospecto generado por chatbot WhatsApp.\n";
        $notas .= "Servicio de interés: " . ($datos['servicio'] ?? '—') . "\n";
        if (! empty($datos['curp'])) {
            $notas .= "CURP: " . $datos['curp'] . "\n";
        }

        $existe = Contacto::where('telefono', $conv->telefono)->exists();
        if (! $existe) {
            Contacto::create([
                'nombre'                => $nombre,
                'apellidos'             => $apellidos,
                'telefono'              => $conv->telefono,
                'email'                 => $datos['correo'] ?? null,
                'origen'                => 'whatsapp',
                'estado_prospecto'      => 'nuevo',
                'fecha_primer_contacto' => now()->toDateString(),
                'notas'                 => $notas,
            ]);
            Log::info("[Chatbot WhatsApp] Prospecto creado: {$conv->telefono} — {$nombreCompleto}");
        }

        $conv->paso = 'completado';
        $conv->save();

        $this->whatsapp->sendText(
            $conv->chat_id,
            "✅ ¡Listo *{$nombre}*!\n\n" .
            "Hemos registrado tu solicitud de *{$datos['servicio']}*.\n\n" .
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
