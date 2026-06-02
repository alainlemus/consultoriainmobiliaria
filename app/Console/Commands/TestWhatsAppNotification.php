<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppNotification extends Command
{
    protected $signature   = 'whatsapp:test {phone? : Número destino (ej: 5531293719)}';
    protected $description = 'Envía un mensaje de prueba de WhatsApp vía OpenWA';

    public function handle(): int
    {
        $phone = $this->argument('phone')
            ?? $this->ask('¿A qué número enviar la prueba? (10 dígitos mexicano)');

        $this->info("Verificando sesión OpenWA...");

        if (! WhatsAppService::isSessionReady()) {
            $this->error('La sesión de OpenWA no está activa. Verifica que el servidor esté corriendo y el QR escaneado.');
            return self::FAILURE;
        }

        $this->info("Sesión activa. Enviando mensaje a {$phone}...");

        $ok = WhatsAppService::sendText(
            $phone,
            "✅ *Mensaje de prueba — CRM Consultoría Inmobiliaria*\n\n" .
            "Este es un mensaje de prueba enviado desde el sistema.\n" .
            "Si lo recibes, la integración con WhatsApp está funcionando correctamente. 🎉"
        );

        if ($ok) {
            $this->info("✅ Mensaje enviado correctamente.");
            return self::SUCCESS;
        }

        $this->error("❌ Error al enviar. Revisa storage/logs/laravel.log para más detalles.");
        return self::FAILURE;
    }
}
