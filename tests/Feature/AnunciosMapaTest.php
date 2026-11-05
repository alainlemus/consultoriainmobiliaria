<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Mapa de anuncios en la app: cada asesor solo ve los anuncios que él colocó;
 * el super_admin ve los de todos (incluidos los retirados).
 */
class AnunciosMapaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        Role::findOrCreate($rol, 'web');
        $u = User::factory()->create(['activo' => true]);
        $u->assignRole($rol);

        return $u;
    }

    private function anuncio(User $u, string $estado = 'activo'): Anuncio
    {
        return Anuncio::create([
            'user_id' => $u->id, 'latitud' => 20.1, 'longitud' => -98.7,
            'tipo' => 'lona', 'estado' => $estado, 'municipio' => 'Pachuca',
            'estado_geo' => 'Hidalgo', 'colocado_en' => '2026-11-04',
        ]);
    }

    public function test_el_asesor_solo_ve_sus_anuncios(): void
    {
        $ana   = $this->usuario('asesor');
        $luis  = $this->usuario('asesor');
        $suyo  = $this->anuncio($ana);
        $this->anuncio($ana, 'retirado');
        $this->anuncio($luis);

        Sanctum::actingAs($ana);
        $ids = collect($this->getJson('/api/v1/anuncios/mapa')->assertOk()->json('data'))->pluck('id');

        $this->assertEquals([$suyo->id], $ids->all());
    }

    public function test_el_super_admin_ve_los_anuncios_de_todos(): void
    {
        $ana   = $this->usuario('asesor');
        $luis  = $this->usuario('asesor');
        $admin = $this->usuario('super_admin');
        $this->anuncio($ana);
        $this->anuncio($luis);
        $this->anuncio($luis, 'retirado');

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/anuncios/mapa')->assertOk()->assertJsonCount(3, 'data');
    }
}
