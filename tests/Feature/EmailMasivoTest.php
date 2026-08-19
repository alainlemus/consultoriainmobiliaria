<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactoResource;
use App\Filament\Resources\ContactoResource\Pages\ListContactos;
use App\Mail\EmailMasivoProspecto;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailMasivoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $asesor;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor',      'guard_name' => 'web']);

        // ContactoResource::canViewAny() exige el permiso 'ViewAny:Contacto' —
        // sin él, mountar ListContactos devuelve 403 y rompe el snapshot de Livewire.
        $permisos = ['ViewAny:Contacto', 'View:Contacto'];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create(['activo' => true]);
        $this->admin->assignRole('super_admin');
        $this->admin->syncPermissions($permisos);

        $this->asesor = User::factory()->create(['activo' => true]);
        $this->asesor->assignRole('asesor');
        $this->asesor->syncPermissions($permisos);
    }

    private function crearContactos(int $n = 3, bool $conEmail = true): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Collection::times($n, fn ($i) => Contacto::create([
            'nombre'           => "Prospecto {$i}",
            'telefono'         => '5512345' . str_pad($i, 3, '0', STR_PAD_LEFT),
            'email'            => $conEmail ? "prospecto{$i}@test.com" : null,
            'asesor_id'        => $this->asesor->id,
            'servicio'         => 'fovissste',
            'estado_prospecto' => 'nuevo',
        ]));
    }

    // ─── Mailable: sustitución de variables ───────────────────────────────

    public function test_mailable_reemplaza_variable_nombre(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'Juan Pérez',
            'telefono'  => '5512345678',
            'email'     => 'juan@test.com',
            'asesor_id' => $this->asesor->id,
        ]);

        $mailable = new EmailMasivoProspecto(
            contacto: $contacto,
            asunto:   'Hola {nombre}',
            cuerpo:   'Buenos días {nombre}, tu teléfono es {telefono}.',
        );

        $mailable->assertSeeInHtml('Juan Pérez');
        $mailable->assertSeeInHtml('5512345678');
        // El asunto se define en el envelope, no en el HTML del cuerpo
        $mailable->assertHasSubject('Hola {nombre}');
    }

    public function test_mailable_reemplaza_variable_servicio(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'Ana López',
            'telefono'  => '5598765432',
            'email'     => 'ana@test.com',
            'servicio'  => 'fovissste',
            'asesor_id' => $this->asesor->id,
        ]);

        $mailable = new EmailMasivoProspecto(
            contacto: $contacto,
            asunto:   'Información sobre {servicio}',
            cuerpo:   'Tu servicio de interés es: {servicio}.',
        );

        // {servicio} se reemplaza con el valor del campo (en mayúsculas tal como está en BD)
        $mailable->assertSeeInHtml('FOVISSSTE');
    }

    public function test_mailable_tiene_asunto_correcto(): void
    {
        $contacto = Contacto::create([
            'nombre'    => 'Test',
            'telefono'  => '5511111111',
            'email'     => 'test@test.com',
            'asesor_id' => $this->asesor->id,
        ]);

        $mailable = new EmailMasivoProspecto(
            contacto: $contacto,
            asunto:   'Oferta especial para ti',
            cuerpo:   'Contenido del mensaje.',
        );

        $mailable->assertHasSubject('Oferta especial para ti');
    }

    // ─── Bulk action: envía emails a contactos con email ──────────────────

    public function test_admin_puede_enviar_email_masivo(): void
    {
        Mail::fake();
        $contactos = $this->crearContactos(3, conEmail: true);

        $this->actingAs($this->admin);

        Livewire::test(ListContactos::class)
            ->callTableBulkAction(
                'enviar_email_masivo',
                $contactos->pluck('id')->toArray(),
                [
                    'asunto' => 'Hola desde el sistema',
                    'cuerpo' => 'Estimado {nombre}, te contactamos sobre tu crédito.',
                ]
            )
            ->assertHasNoErrors();

        Mail::assertQueued(EmailMasivoProspecto::class, 3);
    }

    public function test_bulk_action_omite_contactos_sin_email(): void
    {
        Mail::fake();

        // 2 con email, 2 sin email
        $conEmail  = $this->crearContactos(2, conEmail: true);
        $sinEmail  = $this->crearContactos(2, conEmail: false);
        $todos     = $conEmail->concat($sinEmail);

        $this->actingAs($this->admin);

        Livewire::test(ListContactos::class)
            ->callTableBulkAction(
                'enviar_email_masivo',
                $todos->pluck('id')->toArray(),
                [
                    'asunto' => 'Test',
                    'cuerpo' => 'Mensaje de prueba.',
                ]
            )
            ->assertHasNoErrors();

        // Solo 2 emails (los que tienen email)
        Mail::assertQueued(EmailMasivoProspecto::class, 2);
    }

    public function test_bulk_action_requiere_asunto(): void
    {
        Mail::fake();
        $contactos = $this->crearContactos(1);

        $this->actingAs($this->admin);

        Livewire::test(ListContactos::class)
            ->callTableBulkAction(
                'enviar_email_masivo',
                $contactos->pluck('id')->toArray(),
                [
                    'asunto' => '',  // vacío
                    'cuerpo' => 'Mensaje',
                ]
            )
            ->assertHasTableBulkActionErrors(['asunto']);

        Mail::assertNothingQueued();
    }

    public function test_bulk_action_requiere_cuerpo(): void
    {
        Mail::fake();
        $contactos = $this->crearContactos(1);

        $this->actingAs($this->admin);

        Livewire::test(ListContactos::class)
            ->callTableBulkAction(
                'enviar_email_masivo',
                $contactos->pluck('id')->toArray(),
                [
                    'asunto' => 'Asunto válido',
                    'cuerpo' => '',  // vacío
                ]
            )
            ->assertHasTableBulkActionErrors(['cuerpo']);

        Mail::assertNothingQueued();
    }

    // ─── Acceso por rol ───────────────────────────────────────────────────

    public function test_asesor_no_ve_bulk_action_email(): void
    {
        $this->actingAs($this->asesor);

        Livewire::test(ListContactos::class)
            ->assertTableBulkActionHidden('enviar_email_masivo');
    }

    public function test_admin_ve_bulk_action_email(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListContactos::class)
            ->assertTableBulkActionVisible('enviar_email_masivo');
    }

    // ─── Emails no se envían si todos los seleccionados carecen de email ──

    public function test_no_se_encola_ningun_email_si_ninguno_tiene_correo(): void
    {
        Mail::fake();
        $sinEmail = $this->crearContactos(3, conEmail: false);

        $this->actingAs($this->admin);

        Livewire::test(ListContactos::class)
            ->callTableBulkAction(
                'enviar_email_masivo',
                $sinEmail->pluck('id')->toArray(),
                [
                    'asunto' => 'Asunto',
                    'cuerpo' => 'Cuerpo',
                ]
            )
            ->assertHasNoErrors();

        Mail::assertNothingQueued();
    }
}
