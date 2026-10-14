<?php

namespace Tests\Feature;

use App\Mail\ConfirmacionContacto;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** El logo viaja dentro del correo (cid:) y no depende de que la URL del sitio sea pública. */
class LogoCorreoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super_admin', 'asesor'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
    }

    private function ultimoCorreoCrudo(): string
    {
        $mensajes = app('mailer')->getSymfonyTransport()->messages();

        return $mensajes->last()->getOriginalMessage()->toString();
    }

    public function test_los_correos_propios_incrustan_el_logo(): void
    {
        config(['mail.default' => 'array']);
        $contacto = Contacto::create(['nombre' => 'Ana', 'telefono' => '5511112222', 'email' => 'ana@example.com']);

        Mail::to('ana@example.com')->send(new ConfirmacionContacto($contacto));

        $crudo = $this->ultimoCorreoCrudo();
        $this->assertStringContainsString('src=3D"cid:', $crudo);
        $this->assertStringContainsString('Content-Disposition: inline', $crudo);
        $this->assertStringNotContainsString('/storage/site/', quoted_printable_decode($crudo));
    }

    public function test_el_correo_de_restablecer_contrasena_incrusta_el_logo(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->create(['email' => 'u@example.com']);

        $user->notifyNow(new ResetPassword('token-de-prueba'));

        $crudo = $this->ultimoCorreoCrudo();
        $this->assertStringContainsString('src=3D"cid:', $crudo);
        $this->assertStringContainsString('Content-Disposition: inline', $crudo);
    }
}
