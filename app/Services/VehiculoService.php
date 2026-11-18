<?php

namespace App\Services;

use App\Models\RoutePoint;
use App\Models\User;
use App\Models\Vehiculo;
use App\Models\VehiculoCarga;
use App\Models\VehiculoFoto;
use App\Models\VehiculoMovimiento;
use App\Models\VehiculoReserva;
use App\Models\VehiculoTipo;
use App\Notifications\VehiculoNotificacion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Préstamo de vehículos a asesores.
 *
 * - Fila por orden de llegada: si el horario choca con otra reserva del mismo
 *   vehículo, la nueva queda «en espera» y sube sola cuando la otra se cae.
 * - Solo el super admin autoriza o rechaza; no crea ni edita reservas.
 * - Cada salida y entrega deja km, gasolina, fotos (las del tipo de vehículo)
 *   y quién tiene las llaves: en la oficina (con foto) o con una persona
 *   (cambio de manos con código de 4 dígitos).
 */
class VehiculoService
{
    public const TZ = CitaService::TZ;

    /** «Todo el día»: de 8:00 a 20:00; con resguardo en casa, hasta las 9:00 del día siguiente. */
    public const DIA_INICIO = '08:00';

    public const DIA_FIN = '20:00';

    public const RESGUARDO_HASTA = '09:00';

    /** Minutos antes del inicio en que se puede hacer el checklist de salida. */
    public const MIN_ANTES_SALIDA = 30;

    public const MIN_CODIGO = 5;

    /** Minutos de tolerancia para hacer la salida antes de marcar «No se presentó». */
    public const MIN_NO_PRESENTO = 30;

    public const MIN_NO_PRESENTO_DIA = 120;   // reservas de todo el día (empiezan a las 8:00)

    public const MIN_RECORDATORIO_SALIDA = 30;

    public const MIN_RECORDATORIO_REGRESO = 15;

    public const MIN_RETRASO = 30;

    /** Diferencia entre km del tablero y del GPS que se marca para revisión. */
    public const TOLERANCIA_GPS = 0.15;

    public const ETIQUETAS_EXTRA = ['llaves' => 'Llaves', 'dano' => 'Daño', 'estacionada' => 'Estacionada', 'ticket' => 'Ticket'];

    private const DISK = 'local';

    // ── Reservas ─────────────────────────────────────────────────────────────

    /**
     * @param array{vehiculo_id?:?int, tipo_id?:?int, todo_el_dia:bool, fecha?:?string, inicio?:?string, fin?:?string,
     *              resguardo_casa?:bool, motivo:string, destino?:?string, cita_id?:?int, contacto_id?:?int, expediente_id?:?int} $d
     */
    public function solicitar(array $d, User $asesor): VehiculoReserva
    {
        [$inicio, $fin] = $this->horario($d);

        if ($fin->lte(now())) {
            throw ValidationException::withMessages(['inicio' => 'Elige un horario que todavía no haya pasado.']);
        }

        $yaTiene = VehiculoReserva::where('asesor_id', $asesor->id)
            ->whereIn('estado', [...VehiculoReserva::OCUPAN, VehiculoReserva::EN_ESPERA])
            ->seCruzaCon($inicio->copy()->utc(), $fin->copy()->utc())
            ->exists();
        if ($yaTiene) {
            throw ValidationException::withMessages(['inicio' => 'Ya tienes una reserva en ese horario.']);
        }

        $vehiculo = $this->elegirVehiculo($d, $inicio, $fin);
        $choca = $this->ocupadoPorOtra($vehiculo, $inicio, $fin);

        $reserva = VehiculoReserva::create([
            'vehiculo_id' => $vehiculo->id,
            'asesor_id' => $asesor->id,
            'todo_el_dia' => (bool) $d['todo_el_dia'],
            'inicio' => $inicio->copy()->utc(),
            'fin' => $fin->copy()->utc(),
            'resguardo_casa' => (bool) ($d['resguardo_casa'] ?? false),
            'motivo' => $d['motivo'],
            'destino' => $d['destino'] ?? null,
            'cita_id' => $d['cita_id'] ?? null,
            'contacto_id' => $d['contacto_id'] ?? null,
            'expediente_id' => $d['expediente_id'] ?? null,
            'estado' => $choca ? VehiculoReserva::EN_ESPERA : VehiculoReserva::SOLICITADA,
        ]);

        $extra = $choca ? ' · En espera #'.$this->posicionFila($reserva) : '';
        $licencia = $asesor->licenciaVigente() ? '' : ' · Sin licencia vigente';
        $this->avisarAdmins($reserva, 'solicitada', '🔑 Nueva solicitud de vehículo',
            "{$asesor->name} · {$vehiculo->etiqueta()} · ".$this->cuando($reserva).$extra.$licencia);

        return $reserva->fresh();
    }

    public function autorizar(VehiculoReserva $reserva, User $admin): VehiculoReserva
    {
        if ($reserva->estado !== VehiculoReserva::SOLICITADA) {
            throw ValidationException::withMessages(['estado' => $reserva->estado === VehiculoReserva::EN_ESPERA
                ? 'Esta reserva está en espera: el horario lo tiene otra solicitud. Se podrá autorizar cuando se libere.'
                : 'Esta reserva ya no está pendiente de autorización.']);
        }
        $yaAutorizada = VehiculoReserva::where('vehiculo_id', $reserva->vehiculo_id)->whereKeyNot($reserva->id)
            ->whereIn('estado', [VehiculoReserva::AUTORIZADA, ...VehiculoReserva::FUERA])
            ->seCruzaCon($reserva->inicio, $reserva->fin)->exists();
        if ($yaAutorizada) {
            throw ValidationException::withMessages(['estado' => 'Ese horario ya está autorizado para otro asesor.']);
        }

        $reserva->update(['estado' => VehiculoReserva::AUTORIZADA, 'decidido_por' => $admin->id, 'decidido_at' => now(), 'motivo_decision' => null]);
        $this->avisarAsesor($reserva, 'autorizada', '✅ Vehículo autorizado',
            "{$reserva->vehiculo->etiqueta()} · ".$this->cuando($reserva));

        return $reserva->fresh();
    }

    public function rechazar(VehiculoReserva $reserva, User $admin, ?string $motivo): VehiculoReserva
    {
        if (! $reserva->estaAbierta()) {
            throw ValidationException::withMessages(['estado' => 'Esta reserva ya no se puede rechazar.']);
        }
        $reserva->update(['estado' => VehiculoReserva::RECHAZADA, 'decidido_por' => $admin->id, 'decidido_at' => now(), 'motivo_decision' => $motivo]);
        $this->avisarAsesor($reserva, 'rechazada', '❌ Solicitud de vehículo rechazada',
            $this->cuando($reserva).($motivo ? " · {$motivo}" : ''));
        $this->liberar($reserva);

        return $reserva->fresh();
    }

    public function cancelar(VehiculoReserva $reserva, User $asesor, ?string $motivo): VehiculoReserva
    {
        if ((int) $reserva->asesor_id !== $asesor->id) {
            abort(403);
        }
        if (! $reserva->estaAbierta()) {
            throw ValidationException::withMessages(['estado' => 'Esta reserva ya no se puede cancelar.']);
        }
        $reserva->update(['estado' => VehiculoReserva::CANCELADA, 'motivo_decision' => $motivo]);
        $this->avisarAdmins($reserva, 'cancelada', '↩️ Reserva de vehículo cancelada',
            "{$asesor->name} canceló · {$reserva->vehiculo->etiqueta()} · ".$this->cuando($reserva));
        $this->liberar($reserva);

        return $reserva->fresh();
    }

    /** Sube en la fila a las reservas en espera que ya no chocan con nada. */
    public function liberar(VehiculoReserva $liberada): void
    {
        $enEspera = VehiculoReserva::where('vehiculo_id', $liberada->vehiculo_id)
            ->where('estado', VehiculoReserva::EN_ESPERA)
            ->seCruzaCon($liberada->inicio, $liberada->fin)
            ->orderBy('id')->get();

        foreach ($enEspera as $r) {
            if ($this->ocupadoPorOtra($r->vehiculo, $r->inicio, $r->fin, $r->id)) {
                continue;
            }

            $r->update(['estado' => VehiculoReserva::SOLICITADA]);
            $this->avisarAsesor($r, 'subio', '⬆️ Tu reserva subió en la fila', 'Ya está pendiente de autorización · '.$this->cuando($r));
            $this->avisarAdmins($r, 'solicitada', '🔑 Solicitud lista para autorizar',
                "{$r->asesor->name} · {$r->vehiculo->etiqueta()} · ".$this->cuando($r));
        }
    }

    public function posicionFila(VehiculoReserva $reserva): ?int
    {
        if ($reserva->estado !== VehiculoReserva::EN_ESPERA) {
            return null;
        }

        $cruzan = fn () => VehiculoReserva::where('vehiculo_id', $reserva->vehiculo_id)->whereKeyNot($reserva->id)
            ->seCruzaCon($reserva->inicio, $reserva->fin);

        // Delante van las que ocupan el horario y luego las en espera más antiguas.
        return 1 + $cruzan()->whereIn('estado', VehiculoReserva::OCUPAN)->count()
                 + $cruzan()->where('estado', VehiculoReserva::EN_ESPERA)->where('id', '<', $reserva->id)->count();
    }

    // ── Salida y entrega ─────────────────────────────────────────────────────

    /**
     * @param  array{km:int, gasolina:int, lat?:?float, lng?:?float, notas_danos?:?string, checklist?:?array, llaves_codigo?:?string}  $d
     * @param  array<string, UploadedFile>  $fotos  por clave (tablero, frente, …, llaves)
     * @param  list<UploadedFile>  $fotosDanos
     */
    public function registrarSalida(VehiculoReserva $reserva, User $asesor, array $d, array $fotos, array $fotosDanos = []): VehiculoReserva
    {
        if ((int) $reserva->asesor_id !== $asesor->id) {
            abort(403);
        }
        if ($reserva->estado !== VehiculoReserva::AUTORIZADA) {
            throw ValidationException::withMessages(['estado' => 'La reserva tiene que estar autorizada para iniciar la salida.']);
        }
        if (now()->lt($reserva->inicio->copy()->subMinutes(self::MIN_ANTES_SALIDA))) {
            throw ValidationException::withMessages(['estado' => 'Podrás iniciar la salida '.self::MIN_ANTES_SALIDA.' minutos antes de tu horario.']);
        }
        if (now()->gte($reserva->fin)) {
            throw ValidationException::withMessages(['estado' => 'El horario de esta reserva ya terminó.']);
        }

        $vehiculo = $reserva->vehiculo;
        if ($vehiculo->estado !== Vehiculo::ACTIVO) {
            throw ValidationException::withMessages(['estado' => 'Este vehículo no está disponible ('.(Vehiculo::ESTADOS[$vehiculo->estado] ?? $vehiculo->estado).').']);
        }
        $enUso = $this->reservaEnUso($vehiculo);
        if ($enUso) {
            throw ValidationException::withMessages(['estado' => "El vehículo todavía no se entrega: lo tiene {$enUso->asesor->name}."]);
        }
        if ((int) $d['km'] < $vehiculo->km_actual) {
            throw ValidationException::withMessages(['km' => "El km no puede ser menor al último registrado ({$vehiculo->km_actual})."]);
        }
        $this->exigirFotos($vehiculo->tipo, $fotos);

        // Llaves: ya las tengo, las tomo de la oficina (foto) o me las da alguien (código).
        if ($vehiculo->llaves_en === 'usuario' && (int) $vehiculo->llaves_user_id === $asesor->id) {
            $modo = 'ya_las_tenia';
            $de = null;
        } elseif ($vehiculo->llaves_en === 'oficina') {
            if (empty($fotos['llaves'])) {
                throw ValidationException::withMessages(['fotos.llaves' => 'Toma una foto de las llaves al recogerlas en la oficina.']);
            }
            $modo = 'oficina';
            $de = null;
        } else {
            $de = $this->verificarCodigo($vehiculo, $d['llaves_codigo'] ?? null, $asesor);
            $modo = 'codigo';
        }

        $kmPrevio = (int) $vehiculo->km_actual;

        DB::transaction(function () use ($reserva, $vehiculo, $asesor, $d, $fotos, $fotosDanos, $modo, $de) {
            $mov = VehiculoMovimiento::create([
                'vehiculo_id' => $vehiculo->id, 'reserva_id' => $reserva->id, 'user_id' => $asesor->id,
                'tipo' => VehiculoMovimiento::SALIDA, 'km' => (int) $d['km'], 'gasolina' => (int) $d['gasolina'],
                'lat' => $d['lat'] ?? null, 'lng' => $d['lng'] ?? null, 'notas_danos' => $d['notas_danos'] ?? null,
                'checklist' => array_values($d['checklist'] ?? []),
                'llaves_modo' => $modo, 'llaves_de_id' => $de, 'llaves_destino' => 'usuario',
            ]);
            $this->guardarFotos($mov, $fotos, $fotosDanos);

            $vehiculo->update([
                'km_actual' => (int) $d['km'], 'gasolina_actual' => (int) $d['gasolina'],
                'llaves_en' => 'usuario', 'llaves_user_id' => $asesor->id,
                'llaves_codigo' => null, 'llaves_codigo_user_id' => null, 'llaves_codigo_expira_at' => null,
            ]);
            $reserva->update(['estado' => VehiculoReserva::EN_USO]);
        });

        $this->avisarAdmins($reserva, 'salida', '➡️ Salió un vehículo',
            "{$asesor->name} · {$vehiculo->etiqueta()} · {$d['km']} km · ".Vehiculo::GASOLINA[(int) $d['gasolina']]);

        $sinRegistro = (int) $d['km'] - $kmPrevio;
        if ($kmPrevio > 0 && $sinRegistro > 2) {
            $this->alerta($reserva, "Al salir el tablero marcaba {$d['km']} km y el último registro era {$kmPrevio} km: {$sinRegistro} km sin registro.");
        }

        return $reserva->fresh();
    }

    /**
     * @param  array{km:int, gasolina:int, lat?:?float, lng?:?float, notas_danos?:?string, checklist?:?array, llaves_destino:string}  $d
     * @param  array<string, UploadedFile>  $fotos
     * @param  list<UploadedFile>  $fotosDanos
     */
    public function registrarEntrega(VehiculoReserva $reserva, User $asesor, array $d, array $fotos, array $fotosDanos = []): VehiculoReserva
    {
        if ((int) $reserva->asesor_id !== $asesor->id) {
            abort(403);
        }
        if (! $reserva->estaFuera()) {
            throw ValidationException::withMessages(['estado' => 'Esta reserva no tiene una salida en curso.']);
        }
        $vehiculo = $reserva->vehiculo;
        $kmSalida = (int) ($reserva->salida?->km ?? $vehiculo->km_actual);
        if ((int) $d['km'] < $kmSalida) {
            throw ValidationException::withMessages(['km' => "El km no puede ser menor al de la salida ({$kmSalida})."]);
        }
        $this->exigirFotos($vehiculo->tipo, $fotos);

        $aOficina = $d['llaves_destino'] === 'oficina';
        if ($aOficina && empty($fotos['llaves'])) {
            throw ValidationException::withMessages(['fotos.llaves' => 'Toma una foto de las llaves donde las dejas en la oficina.']);
        }

        DB::transaction(function () use ($reserva, $vehiculo, $asesor, $d, $fotos, $fotosDanos, $aOficina) {
            $mov = VehiculoMovimiento::create([
                'vehiculo_id' => $vehiculo->id, 'reserva_id' => $reserva->id, 'user_id' => $asesor->id,
                'tipo' => VehiculoMovimiento::ENTREGA, 'km' => (int) $d['km'], 'gasolina' => (int) $d['gasolina'],
                'lat' => $d['lat'] ?? null, 'lng' => $d['lng'] ?? null, 'notas_danos' => $d['notas_danos'] ?? null,
                'checklist' => array_values($d['checklist'] ?? []),
                'llaves_modo' => $aOficina ? 'oficina' : 'ya_las_tenia', 'llaves_destino' => $aOficina ? 'oficina' : 'usuario',
            ]);
            $this->guardarFotos($mov, $fotos, $fotosDanos);

            $vehiculo->update(array_merge(
                ['km_actual' => (int) $d['km'], 'gasolina_actual' => (int) $d['gasolina']],
                $aOficina ? ['llaves_en' => 'oficina', 'llaves_user_id' => null] : [],
            ));
            $reserva->update(['estado' => VehiculoReserva::ENTREGADA]);
        });

        $km = (int) $d['km'] - $kmSalida;
        $this->avisarAdmins($reserva, 'entregada', '🏁 Vehículo entregado',
            "{$asesor->name} · {$vehiculo->etiqueta()} · {$km} km recorridos · ".Vehiculo::GASOLINA[(int) $d['gasolina']]
            .' · Llaves '.($aOficina ? 'en oficina' : "con {$asesor->name}"));

        $reserva = $reserva->fresh();
        $gps = $this->recorrido($reserva)['km_gps'];
        if ($gps !== null && max($km, $gps) >= 3 && abs($km - $gps) / max($km, $gps) > self::TOLERANCIA_GPS) {
            $this->alerta($reserva, "El tablero marca {$km} km recorridos y el GPS {$gps} km.");
        }
        if ((int) $d['gasolina'] === 0) {
            $this->alerta($reserva, 'Se entregó con la gasolina en reserva.');
            if ($siguiente = $this->siguienteReserva($vehiculo)) {
                $this->avisarAsesor($siguiente, 'alerta', '⛽ Tu vehículo está en reserva', "{$vehiculo->etiqueta()} se entregó con poca gasolina: carga antes de salir.");
            }
        }
        $this->liberar($reserva);

        return $reserva->fresh();
    }

    // ── Resguardo en casa ────────────────────────────────────────────────────

    /** @param array{km:int, gasolina:int, lat?:?float, lng?:?float} $d @param array<string, UploadedFile> $fotos tablero y estacionada */
    public function registrarResguardo(VehiculoReserva $reserva, User $asesor, array $d, array $fotos): VehiculoReserva
    {
        if ((int) $reserva->asesor_id !== $asesor->id) {
            abort(403);
        }
        if ($reserva->estado !== VehiculoReserva::EN_USO || ! $reserva->resguardo_casa) {
            throw ValidationException::withMessages(['estado' => 'Esta reserva no tiene autorizado guardar el vehículo en casa.']);
        }
        $this->exigirClaves($fotos, ['tablero' => 'Tablero', 'estacionada' => 'Vehículo estacionado']);
        $vehiculo = $reserva->vehiculo;
        if ((int) $d['km'] < $vehiculo->km_actual) {
            throw ValidationException::withMessages(['km' => "El km no puede ser menor al último registrado ({$vehiculo->km_actual})."]);
        }

        DB::transaction(function () use ($reserva, $vehiculo, $asesor, $d, $fotos) {
            $mov = VehiculoMovimiento::create([
                'vehiculo_id' => $vehiculo->id, 'reserva_id' => $reserva->id, 'user_id' => $asesor->id,
                'tipo' => VehiculoMovimiento::RESGUARDO, 'km' => (int) $d['km'], 'gasolina' => (int) $d['gasolina'],
                'lat' => $d['lat'] ?? null, 'lng' => $d['lng'] ?? null,
            ]);
            $this->guardarFotos($mov, $fotos);
            $vehiculo->update(['km_actual' => (int) $d['km'], 'gasolina_actual' => (int) $d['gasolina']]);
            $reserva->update(['estado' => VehiculoReserva::RESGUARDO]);
        });

        return $reserva->fresh();
    }

    /** @param array{km:int, lat?:?float, lng?:?float} $d @param array<string, UploadedFile> $fotos tablero */
    public function retomar(VehiculoReserva $reserva, User $asesor, array $d, array $fotos): VehiculoReserva
    {
        if ((int) $reserva->asesor_id !== $asesor->id) {
            abort(403);
        }
        if ($reserva->estado !== VehiculoReserva::RESGUARDO) {
            throw ValidationException::withMessages(['estado' => 'El vehículo no está guardado en casa.']);
        }
        $this->exigirClaves($fotos, ['tablero' => 'Tablero']);
        $kmResguardo = (int) ($reserva->resguardo?->km ?? $reserva->vehiculo->km_actual);
        if ((int) $d['km'] < $kmResguardo) {
            throw ValidationException::withMessages(['km' => "El km no puede ser menor al que tenía al guardarlo ({$kmResguardo})."]);
        }

        DB::transaction(function () use ($reserva, $asesor, $d, $fotos) {
            $mov = VehiculoMovimiento::create([
                'vehiculo_id' => $reserva->vehiculo_id, 'reserva_id' => $reserva->id, 'user_id' => $asesor->id,
                'tipo' => VehiculoMovimiento::RETOMAR, 'km' => (int) $d['km'], 'lat' => $d['lat'] ?? null, 'lng' => $d['lng'] ?? null,
            ]);
            $this->guardarFotos($mov, $fotos);
            $reserva->vehiculo->update(['km_actual' => (int) $d['km']]);
            $reserva->update(['estado' => VehiculoReserva::EN_USO]);
        });

        $diferencia = (int) $d['km'] - $kmResguardo;
        if ($diferencia > 2) {
            $this->alerta($reserva, "Se guardó en casa con {$kmResguardo} km y a la mañana marcaba {$d['km']} km: {$diferencia} km durante la noche.");
        }

        return $reserva->fresh();
    }

    // ── Gasolina ─────────────────────────────────────────────────────────────

    /** @param array{km:int, litros:float, monto:float, pago:string} $d @param array<string, UploadedFile> $fotos ticket y tablero */
    public function registrarCarga(VehiculoReserva $reserva, User $asesor, array $d, array $fotos): VehiculoCarga
    {
        if ((int) $reserva->asesor_id !== $asesor->id) {
            abort(403);
        }
        if (! $reserva->estaFuera()) {
            throw ValidationException::withMessages(['estado' => 'Solo se registran cargas mientras tienes el vehículo.']);
        }
        if (! array_key_exists($d['pago'], VehiculoCarga::PAGOS)) {
            throw ValidationException::withMessages(['pago' => 'Elige cómo se pagó la gasolina.']);
        }
        $this->exigirClaves($fotos, ['ticket' => 'Ticket', 'tablero' => 'Tablero']);
        $vehiculo = $reserva->vehiculo;
        if ((int) $d['km'] < $vehiculo->km_actual) {
            throw ValidationException::withMessages(['km' => "El km no puede ser menor al último registrado ({$vehiculo->km_actual})."]);
        }

        $carga = DB::transaction(function () use ($reserva, $vehiculo, $asesor, $d, $fotos) {
            $mov = VehiculoMovimiento::create([
                'vehiculo_id' => $vehiculo->id, 'reserva_id' => $reserva->id, 'user_id' => $asesor->id,
                'tipo' => VehiculoMovimiento::CARGA, 'km' => (int) $d['km'],
            ]);
            $this->guardarFotos($mov, $fotos);
            $vehiculo->update(['km_actual' => (int) $d['km']]);

            return VehiculoCarga::create([
                'reserva_id' => $reserva->id, 'vehiculo_id' => $vehiculo->id, 'user_id' => $asesor->id, 'movimiento_id' => $mov->id,
                'km' => (int) $d['km'], 'litros' => $d['litros'], 'monto' => $d['monto'], 'pago' => $d['pago'],
            ]);
        });

        $monto = '$'.number_format((float) $d['monto'], 2);
        $this->avisarAdmins($reserva, 'carga', $d['pago'] === 'reembolso' ? '⛽ Reembolso de gasolina por pagar' : '⛽ Carga de gasolina',
            "{$asesor->name} · {$vehiculo->etiqueta()} · {$monto} · {$d['litros']} L · ".VehiculoCarga::PAGOS[$d['pago']]);

        return $carga;
    }

    public function marcarReembolsado(VehiculoCarga $carga, User $admin): VehiculoCarga
    {
        if ($carga->pago !== 'reembolso' || $carga->reembolsado_at) {
            throw ValidationException::withMessages(['pago' => 'Esta carga no tiene un reembolso pendiente.']);
        }
        $carga->update(['reembolsado_at' => now(), 'reembolsado_por' => $admin->id]);
        $this->avisarAsesor($carga->reserva, 'reembolso', '💵 Reembolso de gasolina pagado',
            '$'.number_format((float) $carga->monto, 2).' · '.$carga->created_at->setTimezone(self::TZ)->format('d/m/Y'));

        return $carga->fresh();
    }

    public function cargaPayload(VehiculoCarga $c): array
    {
        $c->loadMissing('movimiento.fotos');

        return [
            'id' => $c->id,
            'reserva_id' => $c->reserva_id,
            'km' => $c->km,
            'litros' => (float) $c->litros,
            'monto' => (float) $c->monto,
            'pago' => $c->pago,
            'pago_label' => VehiculoCarga::PAGOS[$c->pago] ?? $c->pago,
            'reembolso' => $c->reembolsoEstado(),
            'fotos' => $c->movimiento->fotos->map(fn (VehiculoFoto $f) => [
                'id' => $f->id, 'clave' => $f->clave, 'etiqueta' => self::ETIQUETAS_EXTRA[$f->clave] ?? ucfirst($f->clave),
                'url' => $f->url(), 'thumb_url' => $f->url(true),
            ])->values()->all(),
            'creado_at' => $c->created_at->setTimezone(self::TZ)->toIso8601String(),
        ];
    }

    // ── Prioridad ────────────────────────────────────────────────────────────

    /** Reservas que el super admin desplazaría al priorizar esta. */
    public function conflictos(VehiculoReserva $reserva): Collection
    {
        return VehiculoReserva::with('asesor')->where('vehiculo_id', $reserva->vehiculo_id)->whereKeyNot($reserva->id)
            ->whereIn('estado', [VehiculoReserva::SOLICITADA, VehiculoReserva::AUTORIZADA])
            ->seCruzaCon($reserva->inicio, $reserva->fin)->orderBy('id')->get();
    }

    /**
     * El super admin pasa adelante una visita más importante: la autoriza y las
     * que chocan quedan en espera, con el motivo y la opción de tomar otro vehículo libre.
     */
    public function priorizar(VehiculoReserva $reserva, User $admin, string $motivo): VehiculoReserva
    {
        if (! in_array($reserva->estado, [VehiculoReserva::SOLICITADA, VehiculoReserva::EN_ESPERA], true)) {
            throw ValidationException::withMessages(['estado' => 'Solo se priorizan solicitudes pendientes o en espera.']);
        }
        if (trim($motivo) === '') {
            throw ValidationException::withMessages(['motivo' => 'Escribe por qué se prioriza esta visita.']);
        }
        $fuera = VehiculoReserva::with('asesor')->where('vehiculo_id', $reserva->vehiculo_id)->whereIn('estado', VehiculoReserva::FUERA)
            ->seCruzaCon($reserva->inicio, $reserva->fin)->first();
        if ($fuera) {
            throw ValidationException::withMessages(['estado' => "El vehículo ya salió con {$fuera->asesor->name}; no se puede priorizar hasta que lo entregue."]);
        }

        $desplazadas = $this->conflictos($reserva);
        DB::transaction(function () use ($reserva, $admin, $motivo, $desplazadas) {
            foreach ($desplazadas as $d) {
                $d->update([
                    'estado' => VehiculoReserva::EN_ESPERA, 'estado_previo' => $d->estado,
                    'desplazada_por_id' => $admin->id, 'desplazada_at' => now(), 'desplazada_motivo' => $motivo,
                ]);
            }
            $reserva->update(['estado' => VehiculoReserva::AUTORIZADA, 'decidido_por' => $admin->id, 'decidido_at' => now(), 'motivo_decision' => $motivo]);
        });

        foreach ($desplazadas as $d) {
            $d = $d->fresh();
            $alt = $this->alternativas($d);
            $oferta = $alt ? ' Puedes tomar '.collect($alt)->map->nombre->join(' o ').' desde la app.' : ' Quedaste en espera y te avisaremos si se libera.';
            $this->avisarAsesor($d, 'desplazada', '🔀 Tu reserva de vehículo se movió', $this->cuando($d)." · Se priorizó: {$motivo}.".$oferta);
        }
        $this->avisarAsesor($reserva, 'autorizada', '✅ Vehículo autorizado', "{$reserva->vehiculo->etiqueta()} · ".$this->cuando($reserva));

        return $reserva->fresh();
    }

    /** Vehículos del mismo tipo libres en el horario de una reserva en espera. @return list<Vehiculo> */
    public function alternativas(VehiculoReserva $reserva): array
    {
        $reserva->loadMissing('vehiculo');

        return Vehiculo::where('estado', Vehiculo::ACTIVO)
            ->where('vehiculo_tipo_id', $reserva->vehiculo->vehiculo_tipo_id)
            ->whereKeyNot($reserva->vehiculo_id)->orderBy('id')->get()
            ->reject(fn (Vehiculo $v) => $this->ocupadoPorOtra($v, $reserva->inicio, $reserva->fin, $reserva->id))
            ->values()->all();
    }

    public function cambiarVehiculo(VehiculoReserva $reserva, User $asesor, int $vehiculoId): VehiculoReserva
    {
        if ((int) $reserva->asesor_id !== $asesor->id) {
            abort(403);
        }
        if ($reserva->estado !== VehiculoReserva::EN_ESPERA) {
            throw ValidationException::withMessages(['estado' => 'Solo puedes cambiar de vehículo mientras estás en espera.']);
        }
        $nuevo = collect($this->alternativas($reserva))->firstWhere('id', $vehiculoId);
        if (! $nuevo) {
            throw ValidationException::withMessages(['vehiculo_id' => 'Ese vehículo ya no está libre en tu horario.']);
        }

        $estado = $reserva->estado_previo === VehiculoReserva::AUTORIZADA ? VehiculoReserva::AUTORIZADA : VehiculoReserva::SOLICITADA;
        $reserva->update(['vehiculo_id' => $nuevo->id, 'estado' => $estado]);
        $reserva = $reserva->fresh(['vehiculo', 'asesor']);
        $this->avisarAdmins($reserva, $estado === VehiculoReserva::AUTORIZADA ? 'reasignada' : 'solicitada',
            $estado === VehiculoReserva::AUTORIZADA ? '🔀 Reserva reasignada' : '🔑 Solicitud lista para autorizar',
            "{$asesor->name} tomó {$nuevo->etiqueta()} · ".$this->cuando($reserva));

        return $reserva;
    }

    // ── Recordatorios y alertas (cada 5 minutos) ─────────────────────────────

    /** @return array{recordatorios:int, no_presento:int, retrasos:int} */
    public function revisar(): array
    {
        $n = ['recordatorios' => 0, 'no_presento' => 0, 'retrasos' => 0];

        // Recordatorio de salida y «no se presentó»
        VehiculoReserva::with(['vehiculo', 'asesor'])->where('estado', VehiculoReserva::AUTORIZADA)
            ->where('inicio', '<=', now()->addMinutes(self::MIN_RECORDATORIO_SALIDA))->get()
            ->each(function (VehiculoReserva $r) use (&$n) {
                $tolerancia = $r->todo_el_dia ? self::MIN_NO_PRESENTO_DIA : self::MIN_NO_PRESENTO;
                if (now()->gt($r->inicio->copy()->addMinutes($tolerancia)) || now()->gte($r->fin)) {
                    $r->update(['estado' => VehiculoReserva::NO_PRESENTO]);
                    $this->avisarAsesor($r, 'no_presento', '⌛ Se liberó tu reserva', "No se registró la salida de {$r->vehiculo->etiqueta()} · ".$this->cuando($r));
                    $this->avisarAdmins($r, 'no_presento', '⌛ Asesor no se presentó', "{$r->asesor->name} · {$r->vehiculo->etiqueta()} · ".$this->cuando($r));
                    $this->liberar($r);
                    $n['no_presento']++;
                } elseif (! $r->recordatorio_salida_at && now()->lt($r->inicio)) {
                    $this->avisarAsesor($r, 'recordatorio', '🔑 Tu salida es pronto', "{$r->vehiculo->etiqueta()} · ".$this->cuando($r).' · Haz el checklist de salida en la app.');
                    $r->update(['recordatorio_salida_at' => now()]);
                    $n['recordatorios']++;
                }
            });

        // Recordatorio de regreso y retraso
        VehiculoReserva::with(['vehiculo', 'asesor'])->whereIn('estado', VehiculoReserva::FUERA)
            ->where('fin', '<=', now()->addMinutes(self::MIN_RECORDATORIO_REGRESO))->get()
            ->each(function (VehiculoReserva $r) use (&$n) {
                $hora = $r->fin->copy()->setTimezone(self::TZ)->format('g:i a');
                if (! $r->alerta_retraso_at && now()->gt($r->fin->copy()->addMinutes(self::MIN_RETRASO))) {
                    $r->update(['alerta_retraso_at' => now()]);
                    $this->alerta($r, "No se entregó a tiempo: debía regresar a las {$hora}.");
                    if ($sig = $this->siguienteReserva($r->vehiculo, $r->id)) {
                        $this->avisarAsesor($sig, 'retraso', '⏰ Tu vehículo viene con retraso', "{$r->vehiculo->etiqueta()} todavía no se entrega. Te avisaremos cuando llegue.");
                    }
                    $n['retrasos']++;
                } elseif (! $r->recordatorio_regreso_at && now()->lt($r->fin)) {
                    $this->avisarAsesor($r, 'recordatorio', '⏰ Tu reserva termina pronto', "Regresa {$r->vehiculo->etiqueta()} a las {$hora} y haz el checklist de entrega.");
                    $r->update(['recordatorio_regreso_at' => now()]);
                    $n['recordatorios']++;
                }
            });

        return $n;
    }

    // ── Llaves ───────────────────────────────────────────────────────────────

    /** Quien tiene las llaves (o el super admin/dueño) genera un código de 4 dígitos para entregarlas. */
    public function generarCodigo(Vehiculo $vehiculo, User $user): array
    {
        $esDueno = $vehiculo->llaves_en === 'usuario' && (int) $vehiculo->llaves_user_id === $user->id;
        abort_unless($esDueno || $user->hasRole('super_admin'), 403, 'Solo quien tiene las llaves puede entregarlas.');

        $codigo = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $vehiculo->update([
            'llaves_codigo' => $codigo, 'llaves_codigo_user_id' => $user->id,
            'llaves_codigo_expira_at' => now()->addMinutes(self::MIN_CODIGO),
        ]);

        return ['codigo' => $codigo, 'expira_at' => $vehiculo->llaves_codigo_expira_at->toIso8601String()];
    }

    /** Recibir llaves fuera de una salida (por ejemplo, el dueño recibe del asesor). */
    public function recibirLlaves(Vehiculo $vehiculo, User $user, ?string $codigo): Vehiculo
    {
        $de = $this->verificarCodigo($vehiculo, $codigo, $user);

        DB::transaction(function () use ($vehiculo, $user, $de) {
            VehiculoMovimiento::create([
                'vehiculo_id' => $vehiculo->id, 'reserva_id' => $this->reservaEnUso($vehiculo)?->id, 'user_id' => $user->id,
                'tipo' => VehiculoMovimiento::LLAVES, 'llaves_modo' => 'codigo', 'llaves_de_id' => $de, 'llaves_destino' => 'usuario',
            ]);
            $vehiculo->update([
                'llaves_en' => 'usuario', 'llaves_user_id' => $user->id,
                'llaves_codigo' => null, 'llaves_codigo_user_id' => null, 'llaves_codigo_expira_at' => null,
            ]);
        });

        return $vehiculo->fresh();
    }

    /** Dejar en la oficina las llaves que tengo, con foto. */
    public function dejarLlavesEnOficina(Vehiculo $vehiculo, User $user, UploadedFile $foto, ?float $lat = null, ?float $lng = null): Vehiculo
    {
        $esDueno = $vehiculo->llaves_en === 'usuario' && (int) $vehiculo->llaves_user_id === $user->id;
        abort_unless($esDueno || $user->hasRole('super_admin'), 403, 'Solo quien tiene las llaves puede dejarlas en la oficina.');

        DB::transaction(function () use ($vehiculo, $user, $foto, $lat, $lng) {
            $mov = VehiculoMovimiento::create([
                'vehiculo_id' => $vehiculo->id, 'reserva_id' => $this->reservaEnUso($vehiculo)?->id, 'user_id' => $user->id,
                'tipo' => VehiculoMovimiento::LLAVES, 'lat' => $lat, 'lng' => $lng,
                'llaves_modo' => 'oficina', 'llaves_destino' => 'oficina',
            ]);
            $this->guardarFotos($mov, ['llaves' => $foto]);
            $vehiculo->update(['llaves_en' => 'oficina', 'llaves_user_id' => null, 'llaves_codigo' => null, 'llaves_codigo_user_id' => null, 'llaves_codigo_expira_at' => null]);
        });

        return $vehiculo->fresh();
    }

    // ── Recorrido ────────────────────────────────────────────────────────────

    /** Puntos GPS del asesor entre la salida y la entrega (o ahora, si sigue en uso). */
    public function recorrido(VehiculoReserva $reserva): array
    {
        $salida = $reserva->salida;
        if (! $salida) {
            return ['puntos' => [], 'km_gps' => null];
        }

        $hasta = $reserva->entrega?->created_at ?? now();
        $puntos = RoutePoint::where('user_id', $reserva->asesor_id)
            ->whereBetween('timestamp', [$salida->created_at, $hasta])
            ->orderBy('timestamp')->get(['lat', 'lng', 'timestamp']);

        $km = 0.0;
        for ($i = 1; $i < $puntos->count(); $i++) {
            $km += $this->distanciaKm($puntos[$i - 1]->lat, $puntos[$i - 1]->lng, $puntos[$i]->lat, $puntos[$i]->lng);
        }

        return [
            'puntos' => $puntos->map(fn ($p) => [$p->lat, $p->lng])->all(),
            'km_gps' => $puntos->count() > 1 ? round($km, 1) : null,
        ];
    }

    // ── Payloads para la app ─────────────────────────────────────────────────

    public function vehiculoPayload(Vehiculo $v, User $user): array
    {
        $v->loadMissing(['tipo', 'llavesUsuario']);
        $enUso = $this->reservaEnUso($v);

        return [
            'id' => $v->id,
            'nombre' => $v->nombre,
            'placa' => $v->placa,
            'marca' => $v->marca,
            'modelo' => $v->modelo,
            'color' => $v->color,
            'foto_url' => $v->foto ? Storage::disk('public')->url($v->foto) : null,
            'km_actual' => (int) $v->km_actual,
            'gasolina_actual' => $v->gasolina_actual,
            'estado' => $v->estado !== Vehiculo::ACTIVO ? 'taller' : ($enUso ? 'en_uso' : 'disponible'),
            'tipo' => [
                'id' => $v->tipo->id,
                'nombre' => $v->tipo->nombre,
                'icono' => $v->tipo->icono,
                'fotos_requeridas' => array_values(array_filter($v->tipo->fotosObligatorias(), fn ($f) => $f['clave'] !== 'tablero')),
                'checklist' => array_values($v->tipo->checklist ?? []),
                'licencia_tipo' => $v->tipo->licencia_tipo,
            ],
            'llaves' => [
                'en' => $v->llaves_en,
                'usuario' => $v->llavesUsuario ? ['id' => $v->llavesUsuario->id, 'nombre' => $v->llavesUsuario->name] : null,
                'soy_yo' => $v->llaves_en === 'usuario' && (int) $v->llaves_user_id === $user->id,
            ],
            'en_uso_por' => $enUso?->asesor ? ['id' => $enUso->asesor->id, 'nombre' => $enUso->asesor->name] : null,
            'regreso_previsto' => $enUso?->fin?->setTimezone(self::TZ)->toIso8601String(),
        ];
    }

    public function reservaPayload(VehiculoReserva $r, User $user): array
    {
        $r->loadMissing(['vehiculo.tipo', 'asesor', 'salida.fotos', 'salida.llavesDe', 'entrega.fotos', 'entrega.llavesDe', 'resguardo.fotos', 'cargas.movimiento.fotos', 'desplazadaPor']);
        $alternativas = $r->estado === VehiculoReserva::EN_ESPERA ? $this->alternativas($r) : [];
        $esMia = (int) $r->asesor_id === $user->id;
        $puedeSalida = $esMia && $r->estado === VehiculoReserva::AUTORIZADA
            && now()->gte($r->inicio->copy()->subMinutes(self::MIN_ANTES_SALIDA)) && now()->lt($r->fin);

        return [
            'id' => $r->id,
            'estado' => $r->estado,
            'estado_label' => VehiculoReserva::ESTADOS[$r->estado] ?? $r->estado,
            'posicion_fila' => $this->posicionFila($r),
            'vehiculo' => [...$this->vehiculoPayload($r->vehiculo, $user), 'tipo_nombre' => $r->vehiculo->tipo->nombre],
            'todo_el_dia' => $r->todo_el_dia,
            'inicio' => $r->inicio->setTimezone(self::TZ)->toIso8601String(),
            'fin' => $r->fin->setTimezone(self::TZ)->toIso8601String(),
            'resguardo_casa' => $r->resguardo_casa,
            'motivo' => $r->motivo,
            'destino' => $r->destino,
            'cita_id' => $r->cita_id,
            'contacto_id' => $r->contacto_id,
            'expediente_id' => $r->expediente_id,
            'asesor' => ['id' => $r->asesor->id, 'nombre' => $r->asesor->name],
            'motivo_decision' => $r->motivo_decision,
            'licencia_vigente' => $r->asesor->licenciaVigente(),
            'puede' => [
                'cancelar' => $esMia && $r->estaAbierta(),
                'salida' => $puedeSalida,
                'entrega' => $esMia && $r->estaFuera(),
                'resguardo' => $esMia && $r->estado === VehiculoReserva::EN_USO && $r->resguardo_casa,
                'retomar' => $esMia && $r->estado === VehiculoReserva::RESGUARDO,
                'carga' => $esMia && $r->estaFuera(),
                'cambiar_vehiculo' => $esMia && $alternativas !== [],
            ],
            'salida' => $r->salida ? $this->movimientoPayload($r->salida, $r->vehiculo->tipo) : null,
            'entrega' => $r->entrega ? $this->movimientoPayload($r->entrega, $r->vehiculo->tipo) : null,
            'km_recorridos' => ($r->salida && $r->entrega) ? max(0, $r->entrega->km - $r->salida->km) : null,
            'resguardo' => $r->resguardo ? $this->movimientoPayload($r->resguardo, $r->vehiculo->tipo) : null,
            'desplazada' => $r->desplazada_at ? [
                'motivo' => $r->desplazada_motivo,
                'por' => $r->desplazadaPor ? ['id' => $r->desplazadaPor->id, 'nombre' => $r->desplazadaPor->name] : null,
                'at' => $r->desplazada_at->setTimezone(self::TZ)->toIso8601String(),
            ] : null,
            'alternativas' => array_map(fn (Vehiculo $v) => ['id' => $v->id, 'nombre' => $v->nombre, 'placa' => $v->placa], $alternativas),
            'cargas' => $r->cargas->map(fn (VehiculoCarga $c) => $this->cargaPayload($c))->values()->all(),
            'alertas' => $user->hasRole('super_admin') ? ($r->alertas ?? []) : [],   // son para revisión del admin
            'created_at' => $r->created_at->setTimezone(self::TZ)->toIso8601String(),
        ];
    }

    public function movimientoPayload(VehiculoMovimiento $m, ?VehiculoTipo $tipo = null): array
    {
        $m->loadMissing(['fotos', 'llavesDe']);
        $etiquetas = collect($tipo?->fotosObligatorias() ?? [])->pluck('etiqueta', 'clave')->all() + self::ETIQUETAS_EXTRA;

        return [
            'id' => $m->id,
            'tipo' => $m->tipo,
            'km' => $m->km,
            'gasolina' => $m->gasolina,
            'gasolina_label' => $m->gasolina !== null ? (Vehiculo::GASOLINA[$m->gasolina] ?? null) : null,
            'lat' => $m->lat,
            'lng' => $m->lng,
            'notas_danos' => $m->notas_danos,
            'checklist' => $m->checklist ?? [],
            'llaves_modo' => $m->llaves_modo,
            'llaves_de' => $m->llavesDe ? ['id' => $m->llavesDe->id, 'nombre' => $m->llavesDe->name] : null,
            'fotos' => $m->fotos->map(fn (VehiculoFoto $f) => [
                'id' => $f->id, 'clave' => $f->clave, 'etiqueta' => $etiquetas[$f->clave] ?? ucfirst($f->clave),
                'url' => $f->url(), 'thumb_url' => $f->url(true),
            ])->values()->all(),
            'creado_at' => $m->created_at->setTimezone(self::TZ)->toIso8601String(),
        ];
    }

    public function cuando(VehiculoReserva $r): string
    {
        $i = $r->inicio->copy()->setTimezone(self::TZ)->locale('es');
        $f = $r->fin->copy()->setTimezone(self::TZ)->locale('es');
        if ($r->todo_el_dia) {
            return $i->translatedFormat('l j \\d\\e F').' · todo el día'.($r->resguardo_casa ? ' (se queda en casa)' : '');
        }

        return $i->translatedFormat('l j \\d\\e F, g:i a').' a '.($i->isSameDay($f) ? $f->format('g:i a') : $f->translatedFormat('l j, g:i a'));
    }

    public function reservaEnUso(Vehiculo $v): ?VehiculoReserva
    {
        return VehiculoReserva::where('vehiculo_id', $v->id)->whereIn('estado', VehiculoReserva::FUERA)->with('asesor')->first();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** @return array{0: Carbon, 1: Carbon} en hora de México */
    private function horario(array $d): array
    {
        if (! empty($d['todo_el_dia'])) {
            if (empty($d['fecha'])) {
                throw ValidationException::withMessages(['fecha' => 'Elige el día.']);
            }
            $dia = Carbon::parse($d['fecha'], self::TZ)->startOfDay();
            $inicio = $dia->copy()->setTimeFromTimeString(self::DIA_INICIO);
            $fin = ! empty($d['resguardo_casa'])
                ? $dia->copy()->addDay()->setTimeFromTimeString(self::RESGUARDO_HASTA)
                : $dia->copy()->setTimeFromTimeString(self::DIA_FIN);

            return [$inicio, $fin];
        }

        if (empty($d['inicio']) || empty($d['fin'])) {
            throw ValidationException::withMessages(['inicio' => 'Indica la hora de salida y la de regreso.']);
        }
        $inicio = Carbon::parse($d['inicio'])->setTimezone(self::TZ);
        $fin = Carbon::parse($d['fin'])->setTimezone(self::TZ);
        if ($fin->lte($inicio)) {
            throw ValidationException::withMessages(['fin' => 'La hora de regreso debe ser después de la salida.']);
        }

        return [$inicio, $fin];
    }

    /** El vehículo pedido, o el primero libre del tipo; si ninguno está libre, el primero (queda en espera). */
    private function elegirVehiculo(array $d, Carbon $inicio, Carbon $fin): Vehiculo
    {
        if (! empty($d['vehiculo_id'])) {
            $v = Vehiculo::where('estado', Vehiculo::ACTIVO)->find($d['vehiculo_id']);
            if (! $v) {
                throw ValidationException::withMessages(['vehiculo_id' => 'Ese vehículo no está disponible.']);
            }

            return $v;
        }

        $candidatos = Vehiculo::where('estado', Vehiculo::ACTIVO)
            ->when(! empty($d['tipo_id']), fn ($q) => $q->where('vehiculo_tipo_id', $d['tipo_id']))
            ->orderBy('id')->get();
        if ($candidatos->isEmpty()) {
            throw ValidationException::withMessages(['tipo_id' => 'No hay vehículos de ese tipo disponibles.']);
        }

        return $candidatos->first(fn (Vehiculo $v) => ! $this->ocupadoPorOtra($v, $inicio, $fin)) ?? $candidatos->first();
    }

    private function ocupadoPorOtra(Vehiculo $v, $inicio, $fin, ?int $ignorar = null): bool
    {
        return VehiculoReserva::where('vehiculo_id', $v->id)
            ->when($ignorar, fn ($q) => $q->whereKeyNot($ignorar))
            ->whereIn('estado', VehiculoReserva::OCUPAN)
            ->seCruzaCon(Carbon::parse($inicio)->utc(), Carbon::parse($fin)->utc())
            ->exists();
    }

    /** Devuelve el id de quien entrega las llaves. */
    private function verificarCodigo(Vehiculo $vehiculo, ?string $codigo, User $recibe): int
    {
        $valido = $vehiculo->llaves_codigo !== null && $codigo !== null
            && hash_equals($vehiculo->llaves_codigo, trim($codigo))
            && $vehiculo->llaves_codigo_expira_at?->isFuture();
        if (! $valido) {
            throw ValidationException::withMessages(['llaves_codigo' => 'El código de llaves no es correcto o ya venció. Pide uno nuevo a quien te las entrega.']);
        }
        $de = (int) $vehiculo->llaves_codigo_user_id;
        if ($de === $recibe->id) {
            throw ValidationException::withMessages(['llaves_codigo' => 'Ese código lo generaste tú; lo escribe quien recibe las llaves.']);
        }

        return $de;
    }

    /** Guarda la alerta en la reserva y avisa al super admin. */
    private function alerta(VehiculoReserva $r, string $texto): void
    {
        $r->alertar($texto);
        $r->loadMissing(['vehiculo', 'asesor']);
        $this->avisarAdmins($r, 'alerta', '⚠️ Revisar reserva de vehículo', "{$r->asesor->name} · {$r->vehiculo->etiqueta()} · {$texto}");
    }

    /** La próxima reserva autorizada del vehículo. */
    private function siguienteReserva(Vehiculo $v, ?int $ignorar = null): ?VehiculoReserva
    {
        return VehiculoReserva::with('asesor')->where('vehiculo_id', $v->id)->where('estado', VehiculoReserva::AUTORIZADA)
            ->when($ignorar, fn ($q) => $q->whereKeyNot($ignorar))
            ->where('fin', '>', now())->orderBy('inicio')->first();
    }

    /** @param array<string, UploadedFile> $fotos @param array<string, string> $claves clave => etiqueta */
    private function exigirClaves(array $fotos, array $claves): void
    {
        $faltan = collect($claves)->reject(fn ($etiqueta, $clave) => ! empty($fotos[$clave]))->values();
        if ($faltan->isNotEmpty()) {
            throw ValidationException::withMessages(['fotos' => 'Faltan fotos: '.$faltan->join(', ').'.']);
        }
    }

    /** @param array<string, UploadedFile> $fotos */
    private function exigirFotos(VehiculoTipo $tipo, array $fotos): void
    {
        $faltan = collect($tipo->fotosObligatorias())->reject(fn ($f) => ! empty($fotos[$f['clave']]))->pluck('etiqueta');
        if ($faltan->isNotEmpty()) {
            throw ValidationException::withMessages(['fotos' => 'Faltan fotos: '.$faltan->join(', ').'.']);
        }
    }

    /** @param array<string, UploadedFile> $fotos @param list<UploadedFile> $danos */
    private function guardarFotos(VehiculoMovimiento $mov, array $fotos, array $danos = []): void
    {
        $servicio = new ImagenService;
        $dir = "vehiculos/{$mov->vehiculo_id}/movimientos/{$mov->id}";
        $lista = collect($fotos)->map(fn ($f, $clave) => [$clave, $f])->values()->all();
        foreach ($danos as $f) {
            $lista[] = ['dano', $f];
        }

        foreach ($lista as [$clave, $archivo]) {
            if (! $archivo instanceof UploadedFile) {
                continue;
            }
            $ruta = $servicio->comprimirYGuardar($archivo, $dir, self::DISK);
            VehiculoFoto::create([
                'movimiento_id' => $mov->id, 'clave' => (string) $clave,
                'ruta' => $ruta, 'ruta_thumb' => $servicio->generarThumbnail($ruta, self::DISK),
            ]);
        }
    }

    private function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1.0, sqrt($a)));
    }

    private function avisarAdmins(VehiculoReserva $r, string $tipo, string $titulo, string $cuerpo): void
    {
        User::role('super_admin')->where('activo', true)->get()
            ->each(fn (User $a) => $a->notify(new VehiculoNotificacion($r, $tipo, $titulo, $cuerpo)));
    }

    private function avisarAsesor(VehiculoReserva $r, string $tipo, string $titulo, string $cuerpo): void
    {
        $r->loadMissing('asesor');
        $r->asesor?->notify(new VehiculoNotificacion($r, $tipo, $titulo, $cuerpo));
    }
}
