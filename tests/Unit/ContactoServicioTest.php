<?php

namespace Tests\Unit;

use App\Models\Contacto;
use PHPUnit\Framework\TestCase;

/** El servicio se muestra en mayúsculas sin romper los acentos («CRÉDITO», no «CRéDITO»). */
class ContactoServicioTest extends TestCase
{
    public function test_el_servicio_en_mayusculas_respeta_acentos(): void
    {
        $c = new Contacto(['servicio' => 'Crédito Tradicional FOVISSSTE']);

        $this->assertSame('CRÉDITO TRADICIONAL FOVISSSTE', $c->servicio);
    }
}
