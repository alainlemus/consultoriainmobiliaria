<?php

namespace App\Services;

use App\Models\Acreditado;
use App\Models\Cita;
use App\Models\Cobertura;
use App\Models\User;
use App\Notifications\CitaNotificacion;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Citas de asesoría. Una sola agenda (la del super admin): un hueco ocupado
 * en una oficina lo está en todas. Los huecos salen del horario que cada
 * oficina tiene configurado en el panel.
 */
class CitaService
{
    public const TZ = 'America/Mexico_City';

    /** Oficinas que reciben citas, para la app. */
    public function oficinas(): array
    {
        return Cobertura::conCitas()->get()->map(function (Cobertura $c) {
            $h = $c->horario();
            return [
                'id'                => $c->id,
                'nombre'            => $c->nombre,
                'direccion'         => $c->detalle,
                'dias'              => array_values(array_map('intval', (array) $h['dias'])),
                'inicio'            => $h['inicio'],
                'fin'               => $h['fin'],
                'duracion_min'      => (int) $h['duracion_min'],
                'permite_domicilio' => (bool) $h['permite_domicilio'],
            ];
        })->values()->all();
    }

    /**
     * Huecos de un día para una oficina. Devuelve todos los huecos del horario y
     * si cada uno está libre (ocupado por otra cita activa o ya pasó).
     * @return list<array{inicia:string, termina:string, disponible:bool}>
     */
    public function disponibilidad(Cobertura $oficina, CarbonInterface $fecha, string $tipo = 'oficina'): array
    {
        $h = $oficina->horario();
        $dia = Carbon::parse($fecha->toDateString(), self::TZ);
        if (! in_array($dia->dayOfWeekIso, array_map('intval', (array) $h['dias']), true)) return [];

        $duracion = (int) ($tipo === 'domicilio' ? $h['duracion_domicilio_min'] : $h['duracion_min']);
        $paso     = (int) $h['duracion_min'];
        $inicio   = $dia->copy()->setTimeFromTimeString($h['inicio']);
        $fin      = $dia->copy()->setTimeFromTimeString($h['fin']);

        $ocupadas = Cita::activas()
            ->whereBetween('inicia_at', [$dia->copy()->startOfDay()->utc(), $dia->copy()->endOfDay()->utc()])
            ->get(['inicia_at', 'termina_at']);

        $huecos = [];
        for ($t = $inicio->copy(); $t->copy()->addMinutes($duracion) <= $fin; $t->addMinutes($paso)) {
            $fin_t = $t->copy()->addMinutes($duracion);
            $choca = $ocupadas->contains(fn ($c) => $c->inicia_at->lt($fin_t) && $c->termina_at->gt($t));
            $huecos[] = [
                'inicia'     => $t->toIso8601String(),
                'termina'    => $fin_t->toIso8601String(),
                'disponible' => ! $choca && $t->gt(now()),
            ];
        }

        return $huecos;
    }

    /**
     * Crea la cita en «solicitada» y avisa a los super admin.
     * @param array{tipo:string, cobertura_id:int, inicia_at:string, nombre:string, telefono?:?string, direccion?:?string, notas?:?string, contacto_id?:?int, expediente_id?:?int, acreditado_id?:?int, asesor_id?:?int} $d
     */
    public function solicitar(array $d, User|Acreditado $creador): Cita
    {
        $oficina = Cobertura::conCitas()->findOrFail($d['cobertura_id']);
        $h       = $oficina->horario();
        $tipo    = $d['tipo'] === 'domicilio' ? 'domicilio' : 'oficina';

        if ($tipo === 'domicilio' && ! $h['permite_domicilio']) {
            throw ValidationException::withMessages(['tipo' => 'Esta oficina no ofrece visitas a domicilio.']);
        }
        if ($tipo === 'domicilio' && empty($d['direccion'])) {
            throw ValidationException::withMessages(['direccion' => 'Escribe la dirección donde te visitaremos.']);
        }

        $inicia   = Carbon::parse($d['inicia_at'])->setTimezone(self::TZ);
        $duracion = (int) ($tipo === 'domicilio' ? $h['duracion_domicilio_min'] : $h['duracion_min']);
        $termina  = $inicia->copy()->addMinutes($duracion);

        $this->asegurarDisponible($oficina, $inicia, $termina, $tipo);

        $cita = Cita::create([
            'contacto_id'     => $d['contacto_id'] ?? null,
            'expediente_id'   => $d['expediente_id'] ?? null,
            'acreditado_id'   => $d['acreditado_id'] ?? null,
            'asesor_id'       => $d['asesor_id'] ?? null,
            'cobertura_id'    => $oficina->id,
            'tipo'            => $tipo,
            'direccion'       => $d['direccion'] ?? null,
            'nombre'          => $d['nombre'],
            'telefono'        => $d['telefono'] ?? null,
            'inicia_at'       => $inicia->utc(),
            'termina_at'      => $termina->utc(),
            'estado'          => Cita::SOLICITADA,
            'creada_por_tipo' => $creador instanceof Acreditado ? 'acreditado' : ($creador->hasRole('super_admin') ? 'admin' : 'asesor'),
            'creada_por_id'   => $creador->id,
            'notas'           => $d['notas'] ?? null,
        ]);

        $this->avisarAdmins($cita, 'solicitada', '📅 Nueva cita por confirmar', "{$cita->nombre} · " . $this->cuando($cita) . ' · ' . $cita->lugar());

        return $cita;
    }

    public function confirmar(Cita $cita, User $admin): Cita
    {
        $this->soloActiva($cita);
        $cita->update(['estado' => Cita::CONFIRMADA, 'confirmada_at' => now(), 'atiende_id' => $admin->id]);
        $this->avisarInteresados($cita, 'confirmada', '✅ Cita confirmada', 'Tu cita quedó confirmada: ' . $this->cuando($cita) . ' · ' . $cita->lugar());

        return $cita->fresh();
    }

    public function reagendar(Cita $cita, string $nuevaInicia, User $admin): Cita
    {
        $this->soloActiva($cita);
        $oficina  = $cita->cobertura ?? Cobertura::conCitas()->firstOrFail();
        $inicia   = Carbon::parse($nuevaInicia)->setTimezone(self::TZ);
        $duracion = (int) $cita->inicia_at->diffInMinutes($cita->termina_at);
        $termina  = $inicia->copy()->addMinutes($duracion);

        $this->asegurarDisponible($oficina, $inicia, $termina, $cita->tipo, $cita->id);

        $cita->update([
            'inicia_at' => $inicia->utc(), 'termina_at' => $termina->utc(),
            'estado' => Cita::CONFIRMADA, 'confirmada_at' => now(), 'reagendada_at' => now(), 'atiende_id' => $admin->id,
            'recordatorio_dia_at' => null, 'recordatorio_horas_at' => null,
        ]);
        $this->avisarInteresados($cita, 'reagendada', '🔁 Cita reagendada', 'Tu cita cambió a: ' . $this->cuando($cita) . ' · ' . $cita->lugar());

        return $cita->fresh();
    }

    public function cancelar(Cita $cita, ?string $motivo, User|Acreditado $por): Cita
    {
        $this->soloActiva($cita);
        $cita->update(['estado' => Cita::CANCELADA, 'motivo_cancelacion' => $motivo]);
        $texto = 'La cita del ' . $this->cuando($cita) . ' fue cancelada' . ($motivo ? ": {$motivo}" : '.');
        $this->avisarInteresados($cita, 'cancelada', '❌ Cita cancelada', $texto, excluir: $por);
        if (! ($por instanceof User && $por->hasRole('super_admin'))) {
            $this->avisarAdmins($cita, 'cancelada', '❌ Cita cancelada', "{$cita->nombre} canceló: " . $this->cuando($cita));
        }

        return $cita->fresh();
    }

    public function marcar(Cita $cita, bool $asistio): Cita
    {
        $cita->update(['estado' => $asistio ? Cita::REALIZADA : Cita::NO_ASISTIO]);

        return $cita->fresh();
    }

    /** Recordatorios: un día antes y dos horas antes, a acreditado, asesor y admin. */
    public function recordar(): int
    {
        $enviados = 0;
        $citas = Cita::where('estado', Cita::CONFIRMADA)->where('inicia_at', '>', now())->where('inicia_at', '<=', now()->addHours(26))->get();

        foreach ($citas as $cita) {
            $horas = now()->diffInMinutes($cita->inicia_at, false) / 60;
            if ($horas <= 26 && $horas > 20 && ! $cita->recordatorio_dia_at) {
                $this->avisarInteresados($cita, 'recordatorio', '📅 Tu cita es mañana', $this->cuando($cita) . ' · ' . $cita->lugar(), incluirAdmin: true);
                $cita->update(['recordatorio_dia_at' => now()]);
                $enviados++;
            } elseif ($horas <= 2.5 && ! $cita->recordatorio_horas_at) {
                $this->avisarInteresados($cita, 'recordatorio', '⏰ Tu cita es en 2 horas', $this->cuando($cita) . ' · ' . $cita->lugar(), incluirAdmin: true);
                $cita->update(['recordatorio_horas_at' => now()]);
                $enviados++;
            }
        }

        return $enviados;
    }

    public static function payload(Cita $cita): array
    {
        $cita->loadMissing(['cobertura', 'asesor', 'contacto', 'expediente']);
        $inicia = $cita->inicia_at->setTimezone(self::TZ);

        return [
            'id'            => $cita->id,
            'tipo'          => $cita->tipo,
            'estado'        => $cita->estado,
            'estado_label'  => Cita::ESTADOS[$cita->estado] ?? $cita->estado,
            'nombre'        => $cita->nombre,
            'telefono'      => $cita->telefono,
            'inicia_at'     => $inicia->toIso8601String(),
            'termina_at'    => $cita->termina_at->setTimezone(self::TZ)->toIso8601String(),
            'fecha_texto'   => $inicia->locale('es')->translatedFormat('l j \\d\\e F, g:i a'),
            'oficina'       => $cita->cobertura ? ['id' => $cita->cobertura->id, 'nombre' => $cita->cobertura->nombre, 'direccion' => $cita->cobertura->detalle] : null,
            'direccion'     => $cita->tipo === 'domicilio' ? $cita->direccion : $cita->cobertura?->detalle,
            'lugar'         => $cita->lugar(),
            'notas'         => $cita->notas,
            'motivo_cancelacion' => $cita->motivo_cancelacion,
            'asesor'        => $cita->asesor ? ['id' => $cita->asesor->id, 'nombre' => $cita->asesor->name, 'telefono' => $cita->asesor->telefono] : null,
            'contacto_id'   => $cita->contacto_id,
            'expediente_id' => $cita->expediente_id,
            'folio'         => $cita->expediente?->folio,
            'creada_por'    => $cita->creada_por_tipo,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function asegurarDisponible(Cobertura $oficina, Carbon $inicia, Carbon $termina, string $tipo, ?int $ignorarId = null): void
    {
        $h = $oficina->horario();
        if ($inicia->lte(now())) {
            throw ValidationException::withMessages(['inicia_at' => 'Elige una fecha y hora futura.']);
        }
        if (! in_array($inicia->dayOfWeekIso, array_map('intval', (array) $h['dias']), true)) {
            throw ValidationException::withMessages(['inicia_at' => 'Ese día la oficina no atiende.']);
        }
        $apertura = $inicia->copy()->setTimeFromTimeString($h['inicio']);
        $cierre   = $inicia->copy()->setTimeFromTimeString($h['fin']);
        if ($inicia->lt($apertura) || $termina->gt($cierre)) {
            throw ValidationException::withMessages(['inicia_at' => "El horario de atención es de {$h['inicio']} a {$h['fin']}."]);
        }
        $choca = Cita::activas()
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->where('inicia_at', '<', $termina->copy()->utc())
            ->where('termina_at', '>', $inicia->copy()->utc())
            ->exists();
        if ($choca) {
            throw ValidationException::withMessages(['inicia_at' => 'Ese horario ya está ocupado. Elige otro hueco.']);
        }
    }

    private function soloActiva(Cita $cita): void
    {
        if (! $cita->estaActiva()) {
            throw ValidationException::withMessages(['estado' => 'Esta cita ya no está activa.']);
        }
    }

    private function cuando(Cita $cita): string
    {
        return $cita->inicia_at->setTimezone(self::TZ)->locale('es')->translatedFormat('l j \\d\\e F, g:i a');
    }

    private function avisarAdmins(Cita $cita, string $tipo, string $titulo, string $cuerpo): void
    {
        User::role('super_admin')->where('activo', true)->get()
            ->each(fn (User $a) => $a->notify(new CitaNotificacion($cita, $tipo, $titulo, $cuerpo)));
    }

    private function avisarInteresados(Cita $cita, string $tipo, string $titulo, string $cuerpo, User|Acreditado|null $excluir = null, bool $incluirAdmin = false): void
    {
        $cita->loadMissing(['acreditado', 'asesor', 'atiende']);
        $destinos = collect([$cita->acreditado, $cita->asesor]);
        if ($incluirAdmin) $destinos->push($cita->atiende);

        $destinos->filter()
            ->reject(fn ($d) => $excluir && get_class($d) === get_class($excluir) && $d->id === $excluir->id)
            ->unique(fn ($d) => get_class($d) . $d->id)
            ->each(fn ($d) => $d->notify(new CitaNotificacion($cita, $tipo, $titulo, $cuerpo)));
    }
}
