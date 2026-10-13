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
use App\Notifications\NuevoExpedienteCreado;
use App\Notifications\NuevoProspectoCreado;
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

    // ─── EtapaExpedienteCambiada ───────────────────────────────────────────

    // ─── ComisionGenerada ─────────────────────────────────────────────────

    // ─── ComisionPagada ───────────────────────────────────────────────────

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

    // ─── NuevoProspectoCreado → super_admin ───────────────────────────────

    public function test_super_admin_recibe_notificacion_al_crear_prospecto_con_asesor(): void
    {
        Notification::fake();

        Contacto::create([
            'nombre'    => 'Prospecto App',
            'telefono'  => '5512345678',
            'asesor_id' => $this->asesor->id,
            'origen'    => 'app_movil',
        ]);

        Notification::assertSentTo($this->admin, NuevoProspectoCreado::class);
    }

    public function test_super_admin_recibe_notificacion_al_crear_prospecto_sin_asesor(): void
    {
        Notification::fake();

        Contacto::create([
            'nombre'    => 'Prospecto Sin Asesor',
            'telefono'  => '5598765432',
            'asesor_id' => null,
        ]);

        Notification::assertSentTo($this->admin, NuevoProspectoCreado::class);
    }

    public function test_super_admin_no_recibe_notificacion_al_actualizar_prospecto(): void
    {
        Notification::fake();

        $contacto = Contacto::create([
            'nombre'    => 'Prospecto Existente',
            'asesor_id' => $this->asesor->id,
        ]);

        Notification::assertSentTo($this->admin, NuevoProspectoCreado::class, 1);

        // Actualizar no debe disparar otra notificacion
        $contacto->update(['notas' => 'Actualizado']);

        Notification::assertSentTo($this->admin, NuevoProspectoCreado::class, 1);
    }

    public function test_notificacion_nuevo_prospecto_muestra_origen_app_movil(): void
    {
        Notification::fake();

        Contacto::create([
            'nombre'    => 'Desde App',
            'asesor_id' => $this->asesor->id,
            'origen'    => 'app_movil',
        ]);

        Notification::assertSentTo(
            $this->admin,
            NuevoProspectoCreado::class,
            function (NuevoProspectoCreado $notif) {
                return $notif->contacto->origen === 'app_movil';
            }
        );
    }

    // ─── NuevoExpedienteCreado → super_admin ──────────────────────────────

    public function test_super_admin_recibe_notificacion_al_crear_expediente(): void
    {
        Notification::fake();

        $contacto = Contacto::create(['nombre' => 'Cliente Exp', 'asesor_id' => $this->asesor->id]);

        Expediente::create([
            'asesor_id'        => $this->asesor->id,
            'contacto_id'      => $contacto->id,
            'tipo_tramite_id'  => $this->tipo->id,
            'etapa_tramite_id' => $this->etapa1->id,
            'estado'           => 'en_proceso',
        ]);

        Notification::assertSentTo($this->admin, NuevoExpedienteCreado::class);
    }

    public function test_super_admin_no_recibe_notificacion_al_actualizar_expediente(): void
    {
        Notification::fake();

        $contacto = Contacto::create(['nombre' => 'Cliente Exp2', 'asesor_id' => $this->asesor->id]);

        $exp = Expediente::create([
            'asesor_id'        => $this->asesor->id,
            'contacto_id'      => $contacto->id,
            'tipo_tramite_id'  => $this->tipo->id,
            'etapa_tramite_id' => $this->etapa1->id,
            'estado'           => 'en_proceso',
        ]);

        Notification::assertSentTo($this->admin, NuevoExpedienteCreado::class, 1);

        // Actualizar no debe disparar otra notificacion
        $exp->update(['estado' => 'documentacion']);

        Notification::assertSentTo($this->admin, NuevoExpedienteCreado::class, 1);
    }

    public function test_notificacion_expediente_contiene_datos_del_asesor(): void
    {
        Notification::fake();

        $contacto = Contacto::create(['nombre' => 'Cliente Exp3', 'asesor_id' => $this->asesor->id]);

        Expediente::create([
            'asesor_id'        => $this->asesor->id,
            'contacto_id'      => $contacto->id,
            'tipo_tramite_id'  => $this->tipo->id,
            'etapa_tramite_id' => $this->etapa1->id,
            'estado'           => 'en_proceso',
        ]);

        Notification::assertSentTo(
            $this->admin,
            NuevoExpedienteCreado::class,
            fn (NuevoExpedienteCreado $notif) => $notif->expediente->asesor_id === $this->asesor->id
        );
    }
}
