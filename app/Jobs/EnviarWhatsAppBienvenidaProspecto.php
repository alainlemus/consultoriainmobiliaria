<?php

namespace App\Jobs;

use App\Models\Contacto;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarWhatsAppBienvenidaProspecto implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 20;

    public function __construct(public Contacto $contacto) {}

    public function handle(): void
    {
        $telefono = $this->contacto->celular ?? $this->contacto->telefono ?? null;
        if (! $telefono) return;

        $nombre = trim("{$this->contacto->nombre} {$this->contacto->apellidos}");

        WhatsAppService::sendText(
            $telefono,
            "¡Hola *{$nombre}*! 👋\n\n" .
            "Gracias por contactarnos. Hemos recibido tu información y un asesor " .
            "se pondrá en contacto contigo a la brevedad.\n\n" .
            "Si tienes alguna duda, no dudes en escribirnos. 🏠"
        );
    }
}
