<?php

namespace App\Mail;

use App\Models\Contacto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailMasivoProspecto extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contacto $contacto,
        public string $asunto,
        public string $cuerpo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->asunto);
    }

    public function content(): Content
    {
        // Reemplazar variables en el cuerpo
        $cuerpoPersonalizado = str_replace(
            ['{nombre}', '{telefono}', '{servicio}'],
            [
                $this->contacto->nombre ?? '',
                $this->contacto->telefono ?? '',
                ucfirst($this->contacto->servicio ?? ''),
            ],
            $this->cuerpo
        );

        return new Content(
            markdown: 'emails.email-masivo-prospecto',
            with: [
                'cuerpoHtml'  => $cuerpoPersonalizado,
                'nombreContacto' => $this->contacto->nombre,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
