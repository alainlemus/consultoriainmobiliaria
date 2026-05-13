<?php

namespace App\Mail;

use App\Models\Contacto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmacionContacto extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Contacto $contacto) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibimos tu mensaje — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirmacion-contacto',
        );
    }
}
