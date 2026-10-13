<?php

namespace App\Mail;

use App\Models\Expediente;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Enlace temporal para descargar la carpeta (ZIP) de un expediente. */
class CarpetaExpedienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Expediente $expediente, public string $enlace) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Carpeta del expediente {$this->expediente->folio}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.carpeta-expediente',
            with: ['expediente' => $this->expediente, 'enlace' => $this->enlace, 'horas' => 72],
        );
    }
}
