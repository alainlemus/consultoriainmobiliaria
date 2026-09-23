<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmacionContacto;
use App\Mail\NuevoContactoAdmin;
use App\Models\Contacto;
use App\Models\User;
use App\Notifications\NuevoMensajeContacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Landing de captación de prospectos FOVISSSTE (/fovissste).
 *
 * Reutiliza el mismo pipeline de guardado, deduplicación y notificaciones
 * que el formulario de contacto general (Contacto::create + ContactoObserver),
 * para que estos prospectos aparezcan igual en el CRM y disparen los mismos
 * avisos a asesores/admins. Solo cambia el set de campos capturados.
 */
class FovisssteController extends Controller
{
    private const SITUACIONES = [
        'trabajador_sin_credito' => 'Soy trabajador del Estado y quiero conocer mis opciones',
        'tiene_credito'          => 'Ya tengo crédito FOVISSSTE y quiero ejercerlo',
        'proceso_iniciado'       => 'Ya tengo un proceso FOVISSSTE iniciado',
        'vivienda_seleccionada'  => 'Ya tengo vivienda seleccionada',
        'no_seguro'              => 'No estoy seguro de mi situación',
    ];

    private const BUSQUEDAS = [
        'casa_nueva'  => 'Casa nueva',
        'casa_usada'  => 'Casa usada',
        'terreno'     => 'Terreno / construcción',
        'no_se'       => 'Todavía no lo sé',
    ];

    public function index()
    {
        return view('pages.fovissste', [
            'situaciones' => self::SITUACIONES,
            'busquedas'   => self::BUSQUEDAS,
            'captcha'     => ContactoController::generarCaptcha(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: si el campo trampa viene lleno, es un bot.
        if (filled($request->input('sitio_web'))) {
            Log::info('Lead FOVISSSTE bloqueado por honeypot', ['ip' => $request->ip()]);
            return redirect(route('fovissste.index') . '#formulario')
                ->with('fovissste_enviado', true);
        }

        // Time-trap: un humano tarda al menos unos segundos en llenar el formulario.
        $inicio = (int) $request->input('form_iniciado');
        if ($inicio > 0 && (time() - $inicio) < 3) {
            Log::info('Lead FOVISSSTE bloqueado por envío demasiado rápido', ['ip' => $request->ip()]);
            return redirect(route('fovissste.index') . '#formulario')
                ->with('fovissste_enviado', true);
        }

        // Validar CAPTCHA antes que el resto — mismo mecanismo que el formulario de contacto general.
        $respuestaCaptcha = (int) $request->input('captcha');
        $esperadoCaptcha  = (int) session('captcha_resultado');

        if ($respuestaCaptcha !== $esperadoCaptcha || $esperadoCaptcha === 0) {
            session()->forget('captcha_resultado');
            return back()
                ->withInput()
                ->withErrors(['captcha' => 'Respuesta incorrecta. Intenta de nuevo.']);
        }

        session()->forget('captcha_resultado');

        $validated = $request->validate([
            'nombre'              => 'required|string|max:100',
            'telefono'            => 'required|string|max:20',
            'email'               => 'nullable|email|max:150',
            'municipio'           => 'required|string|max:100',
            'situacion_fovissste' => 'required|in:' . implode(',', array_keys(self::SITUACIONES)),
            'tipo_busqueda'       => 'required|in:' . implode(',', array_keys(self::BUSQUEDAS)),
            'zona'                => 'nullable|string|max:150',
            'mensaje'             => 'nullable|string|max:1000',
            'curp'                => 'nullable|string|size:18|regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/i',
            'privacidad'          => 'accepted',
        ], [
            'nombre.required'              => 'El nombre es obligatorio.',
            'nombre.max'                   => 'El nombre no puede superar 100 caracteres.',
            'telefono.required'            => 'El teléfono es obligatorio.',
            'telefono.max'                 => 'El teléfono no puede superar 20 caracteres.',
            'email.email'                  => 'Ingresa un correo electrónico válido.',
            'email.max'                    => 'El correo no puede superar 150 caracteres.',
            'municipio.required'           => 'Indica el municipio o ciudad donde buscas vivienda.',
            'municipio.max'                => 'El municipio no puede superar 100 caracteres.',
            'situacion_fovissste.required' => 'Selecciona tu situación con FOVISSSTE.',
            'situacion_fovissste.in'       => 'Selecciona una opción válida.',
            'tipo_busqueda.required'       => 'Selecciona qué estás buscando.',
            'tipo_busqueda.in'             => 'Selecciona una opción válida.',
            'zona.max'                     => 'La zona no puede superar 150 caracteres.',
            'mensaje.max'                  => 'El mensaje no puede superar 1000 caracteres.',
            'curp.size'                    => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.regex'                   => 'La CURP no tiene el formato correcto.',
            'privacidad.accepted'          => 'Debes autorizar el tratamiento de tus datos personales para continuar.',
        ]);

        if (isset($validated['curp'])) {
            $validated['curp'] = strtoupper($validated['curp']);
        }

        // Detalle estructurado de la solicitud — se guarda en 'mensaje' porque
        // no hay columnas dedicadas para situación/búsqueda/zona en Contacto,
        // pero queda visible y buscable para el asesor igual que cualquier
        // mensaje del sitio.
        $detalle = "📍 Origen: Landing FOVISSSTE (/fovissste)\n"
            . 'Situación FOVISSSTE: ' . self::SITUACIONES[$validated['situacion_fovissste']] . "\n"
            . 'Busca: ' . self::BUSQUEDAS[$validated['tipo_busqueda']];

        if (filled($validated['zona'] ?? null)) {
            $detalle .= "\nZona de interés: {$validated['zona']}";
        }
        if (filled($validated['mensaje'] ?? null)) {
            $detalle .= "\n\nMensaje del prospecto: {$validated['mensaje']}";
        }

        // Evitar duplicar el prospecto si ya existe por teléfono o CURP.
        $duplicado = Contacto::buscarDuplicado($validated['telefono'], $validated['curp'] ?? null);

        if ($duplicado) {
            $notaNueva = 'Volvió a escribir desde la landing FOVISSSTE (' . now()->format('d/m/Y H:i') . "):\n" . $detalle;

            $duplicado->update([
                'nombre'                => $validated['nombre'],
                'email'                 => $validated['email'] ?? $duplicado->email,
                'curp'                  => $validated['curp'] ?? $duplicado->curp,
                'municipio_uso_credito' => $validated['municipio'],
                'notas'                 => trim(($duplicado->notas ? $duplicado->notas . "\n\n" : '') . $notaNueva),
            ]);

            $contacto = $duplicado;
        } else {
            $contacto = Contacto::create([
                'nombre'                => $validated['nombre'],
                'telefono'              => $validated['telefono'],
                'email'                 => $validated['email'] ?? null,
                'servicio'              => 'fovissste',
                'mensaje'               => $detalle,
                'municipio_uso_credito' => $validated['municipio'],
                'curp'                  => $validated['curp'] ?? null,
                'origen'                => 'landing_fovissste',
                'fecha_primer_contacto' => now()->toDateString(),
            ]);
        }

        // 1. Correo de confirmación al cliente (solo si dejó correo — es opcional en esta landing)
        if ($contacto->email) {
            try {
                Mail::to($contacto->email)->send(new ConfirmacionContacto($contacto));
            } catch (\Throwable $e) {
                Log::error('Error enviando confirmación FOVISSSTE al cliente: ' . $e->getMessage());
            }
        }

        // 2. Correo de aviso al admin
        $correosAdmin = setting_email_list('correo_contacto');
        if (! empty($correosAdmin)) {
            try {
                Mail::to($correosAdmin)->send(new NuevoContactoAdmin($contacto));
            } catch (\Throwable $e) {
                Log::error('Error enviando correo al admin (FOVISSSTE): ' . $e->getMessage());
            }
        }

        // 3. Notificación en panel Filament a todos los super_admin
        try {
            User::role('super_admin')->get()->each(
                fn (User $user) => $user->notify(new NuevoMensajeContacto($contacto))
            );
        } catch (\Throwable $e) {
            Log::error('Error enviando notificación Filament (FOVISSSTE): ' . $e->getMessage());
        }

        return redirect(route('fovissste.index') . '#formulario')
            ->with('fovissste_enviado', true);
    }
}
