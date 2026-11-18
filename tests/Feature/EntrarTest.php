<?php

namespace Tests\Feature;

use App\Models\Acreditado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Login único de la app: un solo formulario para empleados y acreditados. */
class EntrarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super_admin', 'asesor', 'gestor'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
    }

    private function empleado(string $email, string $rol = 'asesor', array $extra = []): User
    {
        $u = User::factory()->create($extra + ['email' => $email, 'password' => 'secreto123', 'activo' => true]);
        $u->assignRole($rol);

        return $u;
    }

    private function acreditado(string $email, array $extra = []): Acreditado
    {
        return Acreditado::create($extra + ['name' => 'María', 'email' => $email, 'password' => 'secreto123', 'telefono' => '7719998888']);
    }

    public function test_un_empleado_entra_y_recibe_tipo_staff_con_sus_roles(): void
    {
        $this->empleado('ana@example.com', 'gestor');

        $this->postJson('/api/v1/auth/entrar', ['email' => ' Ana@Example.com ', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('tipo', 'staff')
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonPath('user.roles', ['gestor'])
            ->assertJsonStructure(['token']);
    }

    public function test_un_acreditado_entra_y_recibe_tipo_acreditado(): void
    {
        $this->acreditado('maria@example.com');

        $this->postJson('/api/v1/auth/entrar', ['email' => 'maria@example.com', 'password' => 'secreto123'])
            ->assertOk()
            ->assertJsonPath('tipo', 'acreditado')
            ->assertJsonPath('acreditado.email', 'maria@example.com')
            ->assertJsonStructure(['token']);
    }

    public function test_mismo_correo_en_las_dos_tablas_gana_la_contrasena_que_coincide(): void
    {
        $this->empleado('doble@example.com');
        $this->acreditado('doble@example.com', ['password' => 'otraClave99']);

        $this->postJson('/api/v1/auth/entrar', ['email' => 'doble@example.com', 'password' => 'otraClave99'])
            ->assertOk()->assertJsonPath('tipo', 'acreditado');
        $this->postJson('/api/v1/auth/entrar', ['email' => 'doble@example.com', 'password' => 'secreto123'])
            ->assertOk()->assertJsonPath('tipo', 'staff');
    }

    public function test_credenciales_incorrectas_y_cuentas_inactivas(): void
    {
        $this->empleado('ana@example.com');
        $this->acreditado('inactiva@example.com', ['activo' => false]);

        $this->postJson('/api/v1/auth/entrar', ['email' => 'ana@example.com', 'password' => 'mal'])
            ->assertStatus(422)->assertJsonPath('errors.email.0', 'Las credenciales son incorrectas.');
        $this->postJson('/api/v1/auth/entrar', ['email' => 'nadie@example.com', 'password' => 'secreto123'])
            ->assertStatus(422);
        $this->postJson('/api/v1/auth/entrar', ['email' => 'inactiva@example.com', 'password' => 'secreto123'])
            ->assertForbidden()->assertJsonMissingPath('tipo');
    }

    public function test_los_logins_anteriores_siguen_funcionando(): void
    {
        $this->empleado('ana@example.com');
        $this->acreditado('maria@example.com');

        $this->postJson('/api/v1/auth/login', ['email' => 'ana@example.com', 'password' => 'secreto123'])->assertOk()->assertJsonMissingPath('tipo');
        $this->postJson('/api/v1/acreditado/auth/login', ['email' => 'maria@example.com', 'password' => 'secreto123'])->assertOk();
    }
}
