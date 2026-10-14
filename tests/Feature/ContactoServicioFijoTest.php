<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactoResource\Pages\EditContacto;
use App\Models\Contacto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactoServicioFijoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_servicio_de_interes_siempre_es_credito_fovissste(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);
        $admin = User::factory()->create(['activo' => true]);
        $admin->assignRole('super_admin');
        $permisos = ['ViewAny:Contacto', 'View:Contacto', 'Update:Contacto'];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $admin->syncPermissions($permisos);
        $this->actingAs($admin);

        $contacto = Contacto::create(['nombre' => 'Prueba', 'telefono' => '5511111111', 'servicio' => 'INFONAVIT']);

        Livewire::test(EditContacto::class, ['record' => $contacto->getKey()])
            ->assertFormSet(['servicio' => 'FOVISSSTE'])
            ->assertFormFieldIsDisabled('servicio')
            ->fillForm(['nombre' => 'Prueba 2'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('FOVISSSTE', $contacto->fresh()->servicio);
    }
}
