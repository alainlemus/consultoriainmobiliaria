<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Models\Contacto;
use App\Services\AsignacionAsesorQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Formulario al que manda el QR impreso del asesor.
 *
 * Captura al prospecto en el navegador, en el momento del escaneo y con el
 * asesor ya puesto, sin depender de que instale la app. Después, en la página
 * de gracias, puede descargar la app y, si quiere, crear su contraseña para
 * entrar directo. Página ligera (sin librerías) pensada para señal 2G.
 */
class QrProspectoController extends Controller
{
    public const CREDITOS = [
        'FOVISSSTE'              => 'FOVISSSTE (trabajo en gobierno / soy maestro)',
        'INFONAVIT'              => 'INFONAVIT (trabajo en empresa privada)',
        'ASESORIA_PERSONALIZADA' => 'No sé cuál me toca',
    ];

    public function formulario(string $codigo)
    {
        $qr = CodigoQr::buscarActivo($codigo);
        abort_unless($qr, 404);

        return view('qr.formulario', ['qr' => $qr, 'creditos' => self::CREDITOS]);
    }

    public function store(Request $request, string $codigo, AsignacionAsesorQr $asignacion)
    {
        $qr = CodigoQr::buscarActivo($codigo);
        abort_unless($qr, 404);

        // Honeypot: un bot llena el campo oculto; respondemos como si todo hubiera salido bien.
        if (filled($request->input('sitio_web'))) {
            Log::info('Formulario QR bloqueado por honeypot', ['ip' => $request->ip(), 'codigo' => $qr->codigo]);
            return redirect()->route('qr.gracias', ['codigo' => $qr->codigo]);
        }

        $data = $request->validate([
            'nombre'     => ['required', 'string', 'max:100'],
            'telefono'   => ['required', 'regex:/^\D*(\d\D*){10}$/'],
            'municipio'  => ['nullable', 'string', 'max:100'],
            'credito'    => ['required', 'in:' . implode(',', array_keys(self::CREDITOS))],
            'email'      => ['nullable', 'email', 'max:150'],
            'privacidad' => ['accepted'],
        ], [
            'nombre.required'     => 'Escribe tu nombre.',
            'telefono.required'   => 'Escribe tu teléfono.',
            'telefono.regex'      => 'El teléfono debe tener 10 dígitos.',
            'credito.required'    => 'Elige una opción.',
            'email.email'         => 'Ese correo no parece válido.',
            'privacidad.accepted' => 'Necesitamos tu permiso para contactarte.',
        ]);

        $contacto = $asignacion->prospectoDesdeFormulario($qr, [
            'nombre'    => trim($data['nombre']),
            'telefono'  => preg_replace('/\D/', '', $data['telefono']),
            'email'     => isset($data['email']) ? strtolower(trim($data['email'])) : null,
            'municipio' => $data['municipio'] ?? null,
            'servicio'  => $data['credito'],
        ]);

        $request->session()->put($this->claveSesion($qr), $contacto->id);

        return redirect()->route('qr.gracias', ['codigo' => $qr->codigo]);
    }

    public function gracias(Request $request, string $codigo)
    {
        $qr = CodigoQr::buscarActivo($codigo);
        abort_unless($qr, 404);

        $contacto = Contacto::find($request->session()->get($this->claveSesion($qr)));

        return view('qr.gracias', [
            'qr'          => $qr,
            'contacto'    => $contacto,
            'playStore'   => $qr->urlPlayStore(),
            'appStore'    => CodigoQr::urlAppStore(),
            'cuentaLista' => (bool) $request->session()->get('qr_cuenta_lista'),
        ]);
    }

    /** Opcional en la página de gracias: crear la contraseña para entrar a la app ya ligado al asesor. */
    public function cuenta(Request $request, string $codigo)
    {
        $qr = CodigoQr::buscarActivo($codigo);
        abort_unless($qr, 404);

        $contacto = Contacto::find($request->session()->get($this->claveSesion($qr)));
        abort_unless($contacto, 419, 'Vuelve a llenar el formulario.');

        $data = $request->validate([
            'email'    => ['required', 'email', 'max:150', 'unique:acreditados,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'email.required'     => 'Escribe tu correo: será tu usuario en la app.',
            'email.unique'       => 'Ya existe una cuenta con ese correo. Descarga la app e inicia sesión.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        Acreditado::create([
            'name'          => $contacto->nombre,
            'email'         => strtolower(trim($data['email'])),
            'password'      => $data['password'],
            'telefono'      => $contacto->telefono,
            'contacto_id'   => $contacto->id,
            'asesor_id'     => $contacto->asesor_id ?? $qr->asesor_id,
            'codigo_qr_id'  => $qr->id,
            'asesor_origen' => 'formulario_web',
        ]);

        if (! $contacto->email) {
            $contacto->update(['email' => strtolower(trim($data['email']))]);
        }

        $request->session()->flash('qr_cuenta_lista', true);

        return redirect()->route('qr.gracias', ['codigo' => $qr->codigo]);
    }

    private function claveSesion(CodigoQr $qr): string
    {
        return "qr_contacto_{$qr->id}";
    }
}
