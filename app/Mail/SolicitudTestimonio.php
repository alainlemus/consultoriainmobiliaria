<?php

namespace App\Mail;

use App\Models\TestimonioToken;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudTestimonio extends Mailable
{
    use Queueable, SerializesModels;

    public string $link;
    public string $nombreCliente;
    public string $nombreEmpresa;

    public function __construct(public TestimonioToken $token)
    {
        $this->link          = route('testimonio.show', $token->token);
        $this->nombreCliente = $token->nombre_destino;
        $this->nombreEmpresa = config('app.name', 'Consultoría Inmobiliaria');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¿Cómo fue tu experiencia? Déjanos tu testimonio — ' . $this->nombreEmpresa,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-testimonio',
            with: [
                'link'          => $this->link,
                'nombreCliente' => $this->nombreCliente,
                'nombreEmpresa' => $this->nombreEmpresa,
                'expiraDias'    => 7,
            ],
        );
    }
}
