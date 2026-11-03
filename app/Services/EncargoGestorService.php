<?php

namespace App\Services;

use App\Models\DocumentoExpediente;
use App\Models\EncargoGestor;
use App\Models\Expediente;
use App\Models\User;
use App\Notifications\EncargoGestorNotificacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Flujo de los encargos a gestores (lo usan la API de la app y el panel):
 *
 *  enviar()          super_admin → gestor            (por_revisar)
 *  enviarRevision()  gestor marca ✓/✗ cada documento (con_observaciones | en_tramite)
 *  reenviar()        super_admin corrigió → gestor   (por_revisar, vuelta + 1)
 *  subirDocumento()  gestor sube lo que tramitó      (solo en_tramite)
 *  alValidar()/alRechazar()  desde ValidacionDocumentoService; cierra el encargo
 *                    cuando todo lo del gestor está validado.
 *
 * Avisos: al gestor lo que le toca hacer; a todos los super_admin lo que hizo el gestor.
 */
class EncargoGestorService
{
    private const DISK = 'local';

    /** Archivos que puede subir el gestor: PDF, Word, fotos y escaneos */
    public const MIMES = 'pdf,doc,docx,jpg,jpeg,png,heic,webp';

    // ── Admin ────────────────────────────────────────────────────────────────

    /**
     * @param int[]         $documentoIds
     * @param string[]|null $datos  secciones de EncargoGestor::SECCIONES_DATOS (null = todas)
     */
    public function enviar(Expediente $expediente, User $gestor, array $documentoIds, string $instrucciones, ?string $fechaEntrega, User $admin, ?array $datos = null): EncargoGestor
    {
        if (! $gestor->hasRole('gestor') || ! $gestor->activo) {
            throw ValidationException::withMessages(['gestor_id' => 'El usuario elegido no es un gestor activo.']);
        }

        $docs = DocumentoExpediente::where('expediente_id', $expediente->id)
            ->whereIn('id', $documentoIds)
            ->whereNotNull('ruta_archivo')
            ->pluck('id');

        if ($docs->isEmpty()) {
            throw ValidationException::withMessages(['documentos' => 'Elige al menos un documento con archivo para el gestor.']);
        }

        $datos = array_values(array_intersect(array_keys(EncargoGestor::SECCIONES_DATOS), $datos ?? array_keys(EncargoGestor::SECCIONES_DATOS)));

        $encargo = DB::transaction(function () use ($expediente, $gestor, $docs, $instrucciones, $fechaEntrega, $admin, $datos) {
            $encargo = EncargoGestor::create([
                'expediente_id' => $expediente->id,
                'gestor_id'     => $gestor->id,
                'enviado_por'   => $admin->id,
                'instrucciones' => $instrucciones,
                'fecha_entrega' => $fechaEntrega,
                'datos_compartidos' => $datos,
                'estado'        => EncargoGestor::POR_REVISAR,
            ]);
            $encargo->documentos()->attach($docs->all());
            $this->evento($encargo, $admin, 'enviado', "{$docs->count()} documentos compartidos");

            return $encargo;
        });

        $folio = $this->folio($encargo);
        $gestor->notify(new EncargoGestorNotificacion(
            $encargo, 'enviado', "📂 Nuevo expediente para tramitar — {$folio}",
            Str::limit($instrucciones, 140) . ' Revisa los documentos antes de empezar.', true,
        ));

        return $encargo;
    }

    /** El admin ya reemplazó lo que estaba mal: el gestor vuelve a revisar solo eso. */
    public function reenviar(EncargoGestor $encargo, User $admin): void
    {
        if ($encargo->estado !== EncargoGestor::CON_OBSERVACIONES) {
            throw ValidationException::withMessages(['estado' => 'Solo se reenvían encargos con observaciones.']);
        }

        $malos = $encargo->documentos()->wherePivot('revision', 'incorrecto')->get();

        // Cada documento marcado mal debe tener un archivo nuevo desde la revisión del gestor
        $sinCambiar = $malos->filter(fn ($d) => $d->pivot->revisado_at && $d->updated_at->lte(\Illuminate\Support\Carbon::parse($d->pivot->revisado_at)));
        if ($sinCambiar->isNotEmpty()) {
            throw ValidationException::withMessages([
                'documentos' => 'Primero reemplaza: ' . $sinCambiar->pluck('nombre')->join(', ') . '.',
            ]);
        }

        DB::transaction(function () use ($encargo, $malos, $admin) {
            $vuelta = $encargo->vuelta + 1;
            foreach ($malos as $doc) {
                $encargo->documentos()->updateExistingPivot($doc->id, ['revision' => 'pendiente', 'nota' => null, 'revisado_at' => null, 'vuelta' => $vuelta]);
            }
            $encargo->update(['estado' => EncargoGestor::POR_REVISAR, 'vuelta' => $vuelta]);
            $this->evento($encargo, $admin, 'reenviado', $malos->pluck('nombre')->join(', '));
        });

        $encargo->gestor?->notify(new EncargoGestorNotificacion(
            $encargo, 'reenviado', "🔁 Te regresaron el expediente — {$this->folio($encargo)}",
            'La administración cambió ' . $malos->count() . ' documento(s). Revísalos para continuar.', true,
        ));
    }

    public function cancelar(EncargoGestor $encargo, User $admin): void
    {
        $encargo->update(['estado' => EncargoGestor::CANCELADO]);
        $this->evento($encargo, $admin, 'cancelado');
        $encargo->gestor?->notify(new EncargoGestorNotificacion(
            $encargo, 'cancelado', "Encargo cancelado — {$this->folio($encargo)}",
            'La administración canceló este encargo. Ya no tienes que tramitarlo.', true, 'gray',
        ));
    }

    // ── Gestor ───────────────────────────────────────────────────────────────

    /**
     * @param array<int, array{documento_id:int, revision:string, nota?:?string}> $revisiones
     */
    public function enviarRevision(EncargoGestor $encargo, User $gestor, array $revisiones): void
    {
        if ($encargo->estado !== EncargoGestor::POR_REVISAR) {
            throw ValidationException::withMessages(['estado' => 'Este expediente no está esperando tu revisión.']);
        }

        $pendientes = $encargo->documentos()->wherePivot('revision', 'pendiente')->pluck('documentos_expediente.id');
        $porId      = collect($revisiones)->keyBy('documento_id');

        foreach ($pendientes as $id) {
            $r = $porId->get($id);
            if (! $r || ! in_array($r['revision'] ?? null, ['correcto', 'incorrecto'], true)) {
                throw ValidationException::withMessages(['revisiones' => 'Marca todos los documentos como correctos o incorrectos.']);
            }
            if ($r['revision'] === 'incorrecto' && blank($r['nota'] ?? null)) {
                throw ValidationException::withMessages(['revisiones' => 'Escribe por qué está mal cada documento incorrecto.']);
            }
        }

        $malos = collect();
        DB::transaction(function () use ($encargo, $gestor, $pendientes, $porId, &$malos) {
            foreach ($pendientes as $id) {
                $r = $porId->get($id);
                $encargo->documentos()->updateExistingPivot($id, [
                    'revision'    => $r['revision'],
                    'nota'        => $r['revision'] === 'incorrecto' ? trim($r['nota']) : null,
                    'revisado_at' => now(),
                    'vuelta'      => $encargo->vuelta,
                ]);
                if ($r['revision'] === 'incorrecto') {
                    $malos->push($id);
                }
            }

            if ($malos->isNotEmpty()) {
                $encargo->update(['estado' => EncargoGestor::CON_OBSERVACIONES]);
                $this->evento($encargo, $gestor, 'revision_con_observaciones', "{$malos->count()} incorrecto(s)");
            } else {
                $encargo->update(['estado' => EncargoGestor::EN_TRAMITE, 'tramite_iniciado_at' => now()]);
                $this->evento($encargo, $gestor, 'tramite_iniciado');
            }
        });

        $folio = $this->folio($encargo);
        if ($malos->isNotEmpty()) {
            $detalle = $encargo->documentos()->whereIn('documentos_expediente.id', $malos)->get()
                ->map(fn ($d) => "{$d->nombre}: “{$d->pivot->nota}”")->join(' · ');
            $this->avisarAdmins($encargo, 'con_observaciones', "⚠️ {$gestor->name} marcó documentos como incorrectos — {$folio}", $detalle, 'danger');
        } else {
            $this->avisarAdmins($encargo, 'tramite_iniciado', "✅ {$gestor->name} empezó el trámite — {$folio}", 'Revisó los documentos y todos están correctos.', 'success');
        }
    }

    public function subirDocumento(EncargoGestor $encargo, User $gestor, UploadedFile $archivo, string $tipo, ?string $notas, ?int $reemplazaId = null): DocumentoExpediente
    {
        if ($encargo->estado !== EncargoGestor::EN_TRAMITE) {
            throw ValidationException::withMessages(['estado' => 'Podrás subir documentos cuando todos los del expediente estén correctos.']);
        }

        $ext  = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension() ?: 'pdf');
        $dir  = "expedientes/{$encargo->expediente_id}/docs";
        $name = 'gestor_' . Str::slug($tipo) . '_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(5)) . '.' . $ext;
        Storage::disk(self::DISK)->putFileAs($dir, $archivo, $name);
        $ruta = "{$dir}/{$name}";

        $datos = [
            'nombre'         => $tipo,
            'estado'         => 'recibido',
            'notas'          => $notas,
            'ruta_archivo'   => $ruta,
            'capturado_at'   => null,
            'validado_at'    => null,
            'validado_por'   => null,
            'motivo_rechazo' => null,
        ];

        // Volver a subir uno que la administración rechazó: se reemplaza el mismo documento
        $doc = $reemplazaId
            ? $encargo->documentosSubidos()->whereKey($reemplazaId)->first()
            : null;

        if ($doc) {
            if ($doc->ruta_archivo) {
                Storage::disk(self::DISK)->delete($doc->ruta_archivo);
            }
            $doc->update($datos);
        } else {
            $doc = DocumentoExpediente::create($datos + [
                'expediente_id'     => $encargo->expediente_id,
                'encargo_gestor_id' => $encargo->id,
                'subido_por_id'     => $gestor->id,
                'tipo'              => $this->tipoUnico($encargo->expediente_id, 'Gestor: ' . $tipo),
                'seccion'           => 'otros',
            ]);
        }

        $this->evento($encargo, $gestor, 'documento_subido', $tipo);
        $this->avisarAdmins($encargo, 'documento_subido', "📄 {$gestor->name} subió un documento — {$this->folio($encargo)}", "{$tipo}. Revísalo y valídalo.");

        return $doc;
    }

    // ── Validación del admin sobre lo que subió el gestor ───────────────────

    public function alValidar(DocumentoExpediente $doc, User $admin): void
    {
        $encargo = $doc->encargoGestor;
        if (! $encargo || $encargo->estado !== EncargoGestor::EN_TRAMITE) {
            return;
        }

        $this->evento($encargo, $admin, 'documento_validado', $doc->nombre);

        $subidos = $encargo->documentosSubidos()->get();
        $todoValidado = $subidos->isNotEmpty() && $subidos->every(fn ($d) => $d->validacion === 'validado');

        if ($todoValidado) {
            $encargo->update(['estado' => EncargoGestor::TERMINADO, 'terminado_at' => now()]);
            $this->evento($encargo, $admin, 'terminado');
            $encargo->gestor?->notify(new EncargoGestorNotificacion(
                $encargo, 'terminado', "🏁 Encargo terminado — {$this->folio($encargo)}",
                'La administración validó todo lo que subiste. ¡Gracias!', true, 'success',
            ));
        } else {
            $encargo->gestor?->notify(new EncargoGestorNotificacion(
                $encargo, 'documento_validado', "✔️ Documento validado — {$this->folio($encargo)}", $doc->nombre, true, 'success',
            ));
        }
    }

    public function alRechazar(DocumentoExpediente $doc, string $motivo, ?User $admin = null): void
    {
        $encargo = $doc->encargoGestor;
        if (! $encargo) {
            return;
        }

        $this->evento($encargo, $admin, 'documento_rechazado', "{$doc->nombre}: {$motivo}");
        $encargo->gestor?->notify(new EncargoGestorNotificacion(
            $encargo, 'documento_rechazado', "⚠️ Te rechazaron un documento — {$this->folio($encargo)}",
            "{$doc->nombre}: {$motivo}", true, 'danger',
        ));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function avisarAdmins(EncargoGestor $encargo, string $tipo, string $titulo, string $mensaje, string $color = 'info'): void
    {
        User::role('super_admin')->where('activo', true)->get()
            ->each(fn (User $admin) => $admin->notify(new EncargoGestorNotificacion($encargo, $tipo, $titulo, $mensaje, false, $color)));
    }

    private function evento(EncargoGestor $encargo, ?User $user, string $tipo, ?string $detalle = null): void
    {
        $encargo->eventos()->create(['user_id' => $user?->id, 'tipo' => $tipo, 'detalle' => $detalle]);
    }

    private function folio(EncargoGestor $encargo): string
    {
        return $encargo->expediente?->folio ?? "#{$encargo->expediente_id}";
    }

    private function tipoUnico(int $expedienteId, string $tipo): string
    {
        $final = $tipo;
        for ($n = 2; DocumentoExpediente::where('expediente_id', $expedienteId)->where('tipo', $final)->where('seccion', 'otros')->exists(); $n++) {
            $final = "{$tipo} ({$n})";
        }

        return $final;
    }
}
