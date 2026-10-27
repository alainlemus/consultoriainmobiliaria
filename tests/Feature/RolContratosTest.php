<?php

namespace Tests\Feature;

use App\Models\ContratoGenerado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolContratosTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioContratos(): User
    {
        $permisos = ['ViewAny:ContratoGenerado', 'View:ContratoGenerado', 'Update:ContratoGenerado'];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $rol = Role::firstOrCreate(['name' => 'Contratos', 'guard_name' => 'web']);
        $rol->syncPermissions($permisos);

        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('Contratos');

        return $user;
    }

    public function test_rol_contratos_aterriza_en_contratos_generados_y_no_en_expedientes(): void
    {
        $user = $this->usuarioContratos();

        $this->actingAs($user)->get('/admin')
            ->assertRedirect('/admin/contrato-generados');

        $this->actingAs($user)->get('/admin/contrato-generados')->assertOk();
        $this->actingAs($user)->get('/admin/expedientes')->assertForbidden();
    }

    public function test_rol_contratos_puede_descargar_pagare_y_word(): void
    {
        $user = $this->usuarioContratos();
        $contrato = ContratoGenerado::create([
            'asesor_id'         => $user->id,
            'local_id'          => 'c-1',
            'acreditado_nombre' => 'Ana Prueba',
            'solidario_nombre'  => 'Luis Solidario',
            'pdf_path'          => 'contratos/c-1.pdf',
        ]);

        $this->actingAs($user)->get(route('pagare.contrato_generado', $contrato) . '?preview=1')->assertOk();
        $this->actingAs($user)->get(route('contratos_generados.word', $contrato))->assertOk();
    }

    public function test_usuario_sin_permiso_no_ve_contratos_generados(): void
    {
        Role::firstOrCreate(['name' => 'capturista', 'guard_name' => 'web']);
        $user = User::factory()->create(['activo' => true]);
        $user->assignRole('capturista');

        $this->actingAs($user)->get('/admin/contrato-generados')->assertForbidden();
    }
}
