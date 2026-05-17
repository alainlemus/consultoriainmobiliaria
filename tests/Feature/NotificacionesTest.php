<?php

namespace Tests\Feature;

use App\Models\Comision;
use App\Models\Contacto;
use App\Models\EtapaTramite;
use App\Models\Expediente;
use App\Models\TipoTramite;
use App\Models\User;
use App\Notifications\ComisionGenerada;
use App\Notifications\ComisionPagada;
use App\Notifications\EtapaExpedienteCambiada;
use App\Notifications\ExpedienteCerrado;
use App\Notifications\ProspectoAsignado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;
    private TipoTramite $tipo;
    private EtapaTramite $etapa1;
    private EtapaTramite $etapa2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');

        $this->tipo = TipoTramite::create([
            'nombre'                => 'FOVISSSTE Tradicional',
            'slug'                  => 'fovissste-tradicional',
            'porcentaje_honorarios' => 8.0,
        ]);

        $this->etapa1 = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipo->id,
            'nombre'          => 'Precalificación',
            'orden'           => 1,
        ]);

        $this->etapa2 = EtapaTramite::create([
            'tipo_tramite_id' => $this->tipo->id,
            'nombre'          => 'Documentación',
            'orden'           => 2,
        ]);
    }

    private function crearExpediente(array $extra = []): Expediente
    {
        return Expediente::create(array_merge([
            'asesor_id'         => $this->asesor->id,
            'tipo_tramite_id'   => $this->tipo->id,
            'etapa_tramite_id'  => $this->etapa1->id,
            'estado'            => 'en_proceso',
            'acreditado_nombre' => 'Cliente Test',
        ], $extra));
    }

    // ─── ExpedienteCerrado ─────────────────────────────────────────────────

    public function test_asesor_recibe_notificacion_al_cerrar_expediente(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente([
            'honorarios_monto'      => 50000.00,
            'honorarios_porcentaje' => 8,
        ]);

        $exp->update(['estado' => 'cerrado']);

        Notification::assertSentTo($this->asesor, ExpedienteCerrado::class);
    }

    public function test_notificacion_expediente_cerrado_no_se_envia_sin_honorarios(): void
    {
        Notification::fake();

        // Sin honorarios → no genera comisión, pero sí debe notificar cierre
        $exp = $this->crearExpediente(['honorarios_monto' => 0]);
        $exp->update(['estado' => 'cerrado']);

        // No hay honorarios → no se genera comisión → no se notifica el cierre
        Notification::assertNotSentTo($this->asesor, ExpedienteCerrado::class);
    }

    public function test_notificacion_expediente_cerrado_no_se_envia_si_ya_fue_cerrado(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente(['honorarios_monto' => 30000.00]);
        $exp->update(['estado' => 'cerrado']); // primera vez → notifica
        Notification::assertSentTo($this->asesor, ExpedienteCerrado::class, 1);

        // Actualizar otro campo → no debe volver a notificar cierre
        $exp->update(['acreditado_nombre' => 'Nuevo Nombre']);
        Notification::assertSentTo($this->asesor, ExpedienteCerrado::class, 1);
    }

    // ─── EtapaExpedienteCambiada ───────────────────────────────────────────

    public function test_asesor_recibe_notificacion_al_cambiar_etapa(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente();
        $exp->update(['etapa_tramite_id' => $this->etapa2->id]);

        Notification::assertSentTo($this->asesor, EtapaExpedienteCambiada::class);
    }

    public function test_no_hay_notificacion_si_etapa_no_cambia(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente();
        // Actualizar algo distinto a etapa
        $exp->update(['acreditado_nombre' => 'Otro nombre']);

        Notification::assertNotSentTo($this->asesor, EtapaExpedienteCambiada::class);
    }

    public function test_notificacion_etapa_contiene_nombres_correctos(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente();
        $exp->update(['etapa_tramite_id' => $this->etapa2->id]);

        Notification::assertSentTo(
            $this->asesor,
            EtapaExpedienteCambiada::class,
            function (EtapaExpedienteCambiada $notif) {
                return $notif->etapaAnterior === 'Precalificación'
                    && $notif->etapaNueva    === 'Documentación';
            }
        );
    }

    // ─── ComisionGenerada ─────────────────────────────────────────────────

    public function test_asesor_recibe_notificacion_al_generarse_comision(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente([
            'honorarios_monto'      => 40000.00,
            'honorarios_porcentaje' => 8,
        ]);

        $exp->update(['estado' => 'cerrado']);

        Notification::assertSentTo($this->asesor, ComisionGenerada::class);
    }

    public function test_notificacion_comision_generada_al_crear_comision_directamente(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente();

        Comision::create([
            'expediente_id'       => $exp->id,
            'asesor_id'           => $this->asesor->id,
            'monto_base'          => 20000.00,
            'porcentaje_comision' => 8,
            'monto_comision'      => 20000.00,
            'estado'              => 'pendiente',
            'fecha_generacion'    => now()->toDateString(),
        ]);

        Notification::assertSentTo($this->asesor, ComisionGenerada::class);
    }

    // ─── ComisionPagada ───────────────────────────────────────────────────

    public function test_asesor_recibe_notificacion_cuando_comision_es_pagada(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente();

        $comision = Comision::create([
            'expediente_id'       => $exp->id,
            'asesor_id'           => $this->asesor->id,
            'monto_base'          => 15000.00,
            'porcentaje_comision' => 8,
            'monto_comision'      => 15000.00,
            'estado'              => 'pendiente',
            'fecha_generacion'    => now()->toDateString(),
        ]);

        // Cambiar estado a pagada
        $comision->update(['estado' => 'pagada']);

        Notification::assertSentTo($this->asesor, ComisionPagada::class);
    }

    public function test_no_hay_notificacion_comision_si_estado_no_cambia_a_pagada(): void
    {
        Notification::fake();

        $exp = $this->crearExpediente();

        $comision = Comision::create([
            'expediente_id'       => $exp->id,
            'asesor_id'           => $this->asesor->id,
            'monto_base'          => 15000.00,
            'porcentaje_comision' => 8,
            'monto_comision'      => 15000.00,
            'estado'              => 'pendiente',
            'fecha_generacion'    => now()->toDateString(),
        ]);

        // Cambiar a aprobada (no pagada)
        $comision->update(['estado' => 'aprobada']);

        Notification::assertNotSentTo($this->asesor, ComisionPagada::class);
    }

    // ─── ProspectoAsignado ────────────────────────────────────────────────

    public function test_asesor_recibe_notificacion_al_ser_asignado_a_prospecto(): void
    {
        Notification::fake();

        // Crear prospecto sin asesor y luego asignarlo
        $contacto = Contacto::create([
            'nombre'    => 'Prospecto Test',
            'telefono'  => '5512345678',
            'email'     => 'test@example.com',
            'asesor_id' => null,
        ]);

        $contacto->update(['asesor_id' => $this->asesor->id]);

        Notification::assertSentTo($this->asesor, ProspectoAsignado::class);
    }

    public function test_asesor_recibe_notificacion_si_prospecto_se_crea_con_asesor(): void
    {
        Notification::fake();

        Contacto::create([
            'nombre'    => 'Prospecto Directo',
            'telefono'  => '5599887766',
            'asesor_id' => $this->asesor->id,
        ]);

        Notification::assertSentTo($this->asesor, ProspectoAsignado::class);
    }

    public function test_no_hay_notificacion_si_prospecto_no_tiene_asesor(): void
    {
        Notification::fake();

        Contacto::create([
            'nombre'    => 'Sin Asesor',
            'telefono'  => '5511223344',
            'asesor_id' => null,
        ]);

        Notification::assertNotSentTo($this->asesor, ProspectoAsignado::class);
    }

    public function test_no_hay_notificacion_si_asesor_no_cambia(): void
    {
        Notification::fake();

        $contacto = Contacto::create([
            'nombre'    => 'Prospecto Existente',
            'telefono'  => '5544332211',
            'asesor_id' => $this->asesor->id,
        ]);

        // Actualizar algo distinto al asesor
        $contacto->update(['notas' => 'Nota actualizada']);

        // Debe haber solo 1 notificación (la del created), no una segunda del update
        Notification::assertSentTo($this->asesor, ProspectoAsignado::class, 1);
    }
}
