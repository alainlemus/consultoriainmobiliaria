<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmacionContacto;
use App\Mail\NuevoContactoAdmin;
use App\Models\Contacto;
use App\Models\User;
use App\Notifications\NuevoMensajeContacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactoController extends Controller
{
    /**
     * Genera un nuevo CAPTCHA matemático y lo guarda en sesión.
     * Devuelve los dos operandos para mostrarlos en la vista.
     */
    public static function generarCaptcha(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        session(['captcha_resultado' => $a + $b]);
        return ['a' => $a, 'b' => $b];
    }

    public function store(Request $request)
    {
        // Validar CAPTCHA antes que el resto
        $respuesta  = (int) $request->input('captcha');
        $esperado   = (int) session('captcha_resultado');

        if ($respuesta !== $esperado || $esperado === 0) {
            // Regenerar captcha para que no quede la sesión anterior
            session()->forget('captcha_resultado');
            return back()
                ->withInput()
                ->withErrors(['captcha' => 'Respuesta incorrecta. Intenta de nuevo.']);
        }

        // Limpiar captcha usado
        session()->forget('captcha_resultado');

        $validated = $request->validate([
            'nombre'   => 'required|string|max:100',
            'telefono' => 'required|string|max:20',
            'email'    => 'required|email|max:150',
            'servicio' => 'required|string|max:60',
            'mensaje'  => 'nullable|string|max:1000',
            'curp'     => 'nullable|string|size:18|regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i',
        ], [
            'nombre.required'   => 'El nombre es obligatorio.',
            'nombre.max'        => 'El nombre no puede superar 100 caracteres.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.max'      => 'El teléfono no puede superar 20 caracteres.',
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Ingresa un correo electrónico válido.',
            'email.max'         => 'El correo no puede superar 150 caracteres.',
            'servicio.required' => 'Selecciona el servicio de interés.',
            'servicio.max'      => 'El servicio seleccionado no es válido.',
            'mensaje.max'       => 'El mensaje no puede superar 1000 caracteres.',
            'curp.size'         => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.regex'        => 'La CURP no tiene el formato correcto.',
        ]);

        if (isset($validated['curp'])) {
            $validated['curp'] = strtoupper($validated['curp']);
        }

        $contacto = Contacto::create([
            ...$validated,
            'origen'               => 'sitio_web',
            'fecha_primer_contacto' => now()->toDateString(),
        ]);

        // 1. Correo de confirmación al cliente
        try {
            Mail::to($contacto->email)->send(new ConfirmacionContacto($contacto));
        } catch (\Throwable $e) {
            Log::error('Error enviando confirmación al cliente: ' . $e->getMessage());
        }

        // 2. Correo de aviso al admin
        $correoAdmin = setting('correo_contacto');
        if ($correoAdmin) {
            try {
                Mail::to($correoAdmin)->send(new NuevoContactoAdmin($contacto));
            } catch (\Throwable $e) {
                Log::error('Error enviando correo al admin: ' . $e->getMessage());
            }
        }

        // 3. Notificación en panel Filament a todos los super_admin
        try {
            User::role('super_admin')->get()->each(
                fn (User $user) => $user->notify(new NuevoMensajeContacto($contacto))
            );
        } catch (\Throwable $e) {
            Log::error('Error enviando notificación Filament: ' . $e->getMessage());
        }

        return redirect()->route('home', '#contacto')
            ->with('success', '¡Gracias! Recibimos tu mensaje. Te contactaremos pronto.');
    }
}
