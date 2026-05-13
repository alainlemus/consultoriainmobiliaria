<?php

namespace App\Mail;

use App\Models\Contacto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NuevoContactoAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Contacto $contacto) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📬 Nuevo mensaje de contacto — ' . $this->contacto->nombre,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nuevo-contacto-admin',
        );
    }
}
