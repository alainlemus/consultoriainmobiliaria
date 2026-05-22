<?php

namespace App\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReporteGestion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array  $datos,
        public string $tipo,
        public string $periodo,
    ) {}

    public function envelope(): Envelope
    {
        $tipoLabel = match($this->tipo) {
            'diario'  => 'Diario',
            'semanal' => 'Semanal',
            'mensual' => 'Mensual',
            default   => ucfirst($this->tipo),
        };

        return new Envelope(
            subject: "Reporte {$tipoLabel} de Gestión — {$this->periodo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reporte-gestion',
            with: [
                'tipo'    => $this->tipo,
                'periodo' => $this->periodo,
                'datos'   => $this->datos,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('reportes.reporte-gestion', ['datos' => $this->datos])
            ->setPaper('letter', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $tipoSlug = $this->tipo;
        $filename  = "reporte-{$tipoSlug}-{$this->datos['desde']->format('Y-m-d')}.pdf";

        return [
            Attachment::fromData(fn () => $pdf->output(), $filename)
                ->withMime('application/pdf'),
        ];
    }
}
