<?php

namespace App\Http\Controllers;

use App\Models\Testimonio;
use App\Models\TestimonioToken;
use Illuminate\Http\Request;

class TestimonioPublicoController extends Controller
{
    /** Muestra el formulario si el token es válido */
    public function show(string $token)
    {
        $tk = $this->resolverToken($token);

        return view('pages.testimonio', [
            'token'    => $token,
            'nombre'   => $tk->nombre_destino,
            'servicio' => $tk->expediente->tipoTramite->nombre ?? null,
        ]);
    }

    /** Procesa el envío */
    public function store(Request $request, string $token)
    {
        $tk = $this->resolverToken($token);

        $data = $request->validate([
            'nombre'     => ['required', 'string', 'max:100'],
            'ciudad'     => ['nullable', 'string', 'max:100'],
            'servicio'   => ['nullable', 'string', 'max:50'],
            'estrellas'  => ['required', 'integer', 'min:1', 'max:5'],
            'testimonio' => ['required', 'string', 'min:20', 'max:1000'],
        ], [
            'nombre.required'      => 'Tu nombre es obligatorio.',
            'nombre.max'           => 'El nombre no puede superar 100 caracteres.',
            'estrellas.required'   => 'Por favor selecciona una calificación.',
            'estrellas.min'        => 'La calificación mínima es 1 estrella.',
            'estrellas.max'        => 'La calificación máxima es 5 estrellas.',
            'testimonio.required'  => 'Por favor escribe tu experiencia.',
            'testimonio.min'       => 'Tu testimonio debe tener al menos 20 caracteres.',
            'testimonio.max'       => 'Tu testimonio no puede superar 1000 caracteres.',
        ]);

        $data['activo'] = false;
        $data['orden']  = Testimonio::max('orden') + 1;

        Testimonio::create($data);

        // Marcar token como usado — ya no puede volver a accederse
        $tk->marcarUsado();

        return redirect()->route('testimonio.gracias');
    }

    public function gracias()
    {
        return view('pages.testimonio-gracias');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function resolverToken(string $token): TestimonioToken
    {
        $tk = TestimonioToken::with('expediente.tipoTramite')
            ->where('token', $token)
            ->first();

        // Token no existe
        if (! $tk) {
            abort(404, 'El enlace no es válido.');
        }

        // Token ya fue usado
        if (! is_null($tk->usado_at)) {
            abort(410, 'Este enlace ya fue utilizado.');
        }

        // Token expirado
        if ($tk->expires_at->isPast()) {
            abort(410, 'Este enlace ha expirado. Solicita uno nuevo a tu asesor.');
        }

        return $tk;
    }
}
