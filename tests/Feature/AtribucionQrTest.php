<?php

namespace Tests\Feature;

use App\Models\CodigoQr;
use App\Models\EscaneoQr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fase 2 del QR: atribuir la instalación al escaneo cuando la tienda no lo dice.
 *  - cruce por huella (IP + plataforma + versión del sistema + modelo, 48 h)
 *  - no adivinar si es ambiguo; consumir el escaneo una sola vez
 *  - el registro guarda cómo llegó el código (referrer / huella / manual)
 */
class AtribucionQrTest extends TestCase
{
    use RefreshDatabase;

    private CodigoQr $qr;
    private CodigoQr $otroQr;

    private const UA_ANDROID_13 = 'Mozilla/5.0 (Linux; Android 13; SM-A135M) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';
    private const UA_IPHONE_17  = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'asesor', 'guard_name' => 'web']);

        $asesor = User::factory()->create(['activo' => true, 'name' => 'Juan Pérez']);
        $otro   = User::factory()->create(['activo' => true, 'name' => 'Ana López']);
        $asesor->assignRole('asesor');
        $otro->assignRole('asesor');

        $this->qr     = CodigoQr::create(['asesor_id' => $asesor->id, 'codigo' => 'K7M2PX', 'medio' => 'lona']);
        $this->otroQr = CodigoQr::create(['asesor_id' => $otro->id,   'codigo' => 'ANA001', 'medio' => 'tarjeta']);
    }

    private function escaneo(CodigoQr $qr, string $ua, string $ip = '127.0.0.1', ?string $hace = null): EscaneoQr
    {
        return EscaneoQr::create([
            'codigo_qr_id' => $qr->id,
            'plataforma'   => EscaneoQr::plataformaDesde($ua),
            'ip'           => $ip,
            'user_agent'   => $ua,
            'created_at'   => $hace ? now()->sub($hace) : now()->subMinutes(5),
        ]);
    }

    public function test_iphone_se_atribuye_por_ip_y_version_del_sistema(): void
    {
        $e = $this->escaneo($this->qr, self::UA_IPHONE_17);

        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios', 'os_version' => '17.5.1', 'modelo' => 'iPhone 13'])
            ->assertOk()
            ->assertJson(['codigo' => 'K7M2PX', 'asesor' => ['nombre' => 'Juan Pérez']]);

        $e->refresh();
        $this->assertNotNull($e->atribuido_at);
        $this->assertSame('huella', $e->atribucion_tipo);
    }

    public function test_version_del_sistema_distinta_no_se_atribuye(): void
    {
        $this->escaneo($this->qr, self::UA_IPHONE_17);

        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios', 'os_version' => '16.7'])
            ->assertOk()->assertJson(['codigo' => null]);
    }

    public function test_ip_distinta_o_plataforma_distinta_no_se_atribuye(): void
    {
        $this->escaneo($this->qr, self::UA_IPHONE_17, '10.9.9.9');
        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios'])->assertJson(['codigo' => null]);

        $this->escaneo($this->qr, self::UA_IPHONE_17);
        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'android'])->assertJson(['codigo' => null]);
    }

    public function test_escaneo_viejo_fuera_de_la_ventana_no_cuenta(): void
    {
        $this->escaneo($this->qr, self::UA_IPHONE_17, '127.0.0.1', '3 days');
        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios'])->assertJson(['codigo' => null]);
    }

    public function test_dos_qr_distintos_desde_la_misma_ip_es_ambiguo_y_no_adivina(): void
    {
        $this->escaneo($this->qr,     self::UA_IPHONE_17);
        $this->escaneo($this->otroQr, self::UA_IPHONE_17);

        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios'])->assertJson(['codigo' => null]);
        $this->assertSame(0, EscaneoQr::whereNotNull('atribuido_at')->count());
    }

    public function test_en_android_el_modelo_desempata(): void
    {
        $this->escaneo($this->qr,     self::UA_ANDROID_13);
        $this->escaneo($this->otroQr, 'Mozilla/5.0 (Linux; Android 13; moto g22) Chrome/120 Mobile');

        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'android', 'os_version' => '13', 'modelo' => 'SM-A135M'])
            ->assertJson(['codigo' => 'K7M2PX']);
    }

    public function test_un_escaneo_se_consume_una_sola_vez(): void
    {
        $this->escaneo($this->qr, self::UA_IPHONE_17);

        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios'])->assertJson(['codigo' => 'K7M2PX']);
        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios'])->assertJson(['codigo' => null]);
    }

    public function test_qr_pausado_no_se_atribuye(): void
    {
        $this->qr->update(['activo' => false]);
        $this->escaneo($this->qr, self::UA_IPHONE_17);
        $this->postJson('/api/v1/acreditado/atribucion', ['plataforma' => 'ios'])->assertJson(['codigo' => null]);
    }

    public function test_registro_guarda_el_origen_del_codigo_y_consume_el_escaneo_android(): void
    {
        $e = $this->escaneo($this->qr, self::UA_ANDROID_13);

        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name' => 'María', 'email' => 'maria@example.com',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'codigo_asesor' => 'K7M2PX', 'asesor_origen' => 'referrer',
        ])->assertCreated();

        $this->assertDatabaseHas('acreditados', ['email' => 'maria@example.com', 'asesor_origen' => 'referrer']);
        $this->assertSame('referrer', $e->fresh()->atribucion_tipo);
    }

    public function test_registro_sin_origen_queda_como_codigo_manual_y_origen_invalido_es_422(): void
    {
        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name' => 'Pedro', 'email' => 'pedro@example.com',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'codigo_asesor' => 'K7M2PX',
        ])->assertCreated();
        $this->assertDatabaseHas('acreditados', ['email' => 'pedro@example.com', 'asesor_origen' => 'codigo_manual']);

        $this->postJson('/api/v1/acreditado/auth/registro', [
            'name' => 'X', 'email' => 'x@example.com',
            'password' => 'secreto123', 'password_confirmation' => 'secreto123',
            'codigo_asesor' => 'K7M2PX', 'asesor_origen' => 'inventado',
        ])->assertStatus(422);
    }
}
