<?php

namespace App\Http\Controllers\Api\V1\Acreditado;

use App\Http\Controllers\Controller;
use App\Models\Acreditado;
use App\Models\CodigoQr;
use App\Services\AsignacionAsesorQr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/registro
    // ─────────────────────────────────────────────────────────────────────────
    public function registro(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:acreditados,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'curp'     => ['nullable', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d$/i', 'unique:acreditados,curp'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'nss'      => ['nullable', 'string', 'max:15'],
            // Código impreso junto al QR del asesor (lona, tarjeta, panfleto)
            'codigo_asesor' => ['nullable', 'string', 'max:20'],
            // Cómo llegó el código a la app: lo tecleó la persona (default), lo
            // entregó Play Store (referrer) o lo adivinó el cruce por huella (huella).
            'asesor_origen' => ['nullable', 'in:codigo_manual,referrer,huella'],
        ], [
            'email.unique' => 'Ya existe una cuenta con este correo. Inicia sesión o recupera tu contraseña.',
            'curp.unique'  => 'Ya existe una cuenta con esta CURP. Inicia sesión con el correo que usaste al registrarte.',
        ]);

        // Si mandan código, tiene que existir y estar activo: mejor avisar que
        // dejar la cuenta sin asesor cuando la persona sí lo escribió.
        $codigoQr = null;
        if (! empty($data['codigo_asesor'])) {
            $codigoQr = CodigoQr::buscarActivo($data['codigo_asesor']);
            if (! $codigoQr) {
                throw ValidationException::withMessages([
                    'codigo_asesor' => 'Ese código de asesor no existe o ya no está activo. Revísalo o déjalo vacío.',
                ]);
            }
        }

        $acreditado = Acreditado::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'curp'     => ! empty($data['curp']) ? strtoupper($data['curp']) : null,
            'telefono' => $data['telefono'] ?? null,
            'nss'      => $data['nss'] ?? null,
        ]);

        // Ligar al asesor del QR y avisarle (aún no pide asesoría; que lo contacte)
        if ($codigoQr) {
            app(AsignacionAsesorQr::class)->ligarAcreditado($acreditado, $codigoQr, $data['asesor_origen'] ?? 'codigo_manual');
        } else {
            // Sin código: si llenó el formulario del QR con este teléfono, ya tiene asesor
            app(AsignacionAsesorQr::class)->ligarPorContactoExistente($acreditado);
        }

        // Install Referrer (Android): el escaneo ya no se puede cruzar por huella
        // con otro celular. Se marca el más reciente sin atribuir de ese QR.
        if ($codigoQr && ($data['asesor_origen'] ?? null) === 'referrer') {
            $codigoQr->escaneos()->whereNull('atribuido_at')->where('plataforma', 'android')
                ->latest('created_at')->first()
                ?->update(['atribuido_at' => now(), 'atribucion_tipo' => 'referrer']);
        }

        // Intentar vincular automáticamente con expediente por CURP
        $expediente = $acreditado->vincularExpedientePorCurp();

        $token = $acreditado->createToken('acreditado-app')->plainTextToken;

        return response()->json([
            'message'            => 'Cuenta creada correctamente.',
            'token'              => $token,
            'acreditado'         => $acreditado->toApiArray(),
            'expediente_vinculado' => $expediente ? [
                'folio'  => $expediente->folio,
                'estado' => $expediente->estado,
                'etapa'  => $expediente->etapa?->nombre,
            ] : null,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/codigo-asesor/{codigo}  (público)
    // La app lo llama mientras la persona teclea el código del QR para
    // confirmarle "Tu asesor será X" antes de crear la cuenta.
    // ─────────────────────────────────────────────────────────────────────────
    public function codigoAsesor(string $codigo): JsonResponse
    {
        $codigoQr = CodigoQr::buscarActivo($codigo);

        if (! $codigoQr) {
            return response()->json(['message' => 'Código de asesor no válido.'], 404);
        }

        return response()->json([
            'codigo' => $codigoQr->codigo,
            'asesor' => [
                'id'     => $codigoQr->asesor_id,
                'nombre' => $codigoQr->asesor?->name,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /v1/acreditado/auth/codigo-asesor  (con token)
    // Quien se registró sin código y después recibe una tarjeta lo liga aquí.
    // Solo si aún no tiene asesor: el asesor no se cambia desde la app.
    // ─────────────────────────────────────────────────────────────────────────
    public function ligarCodigoAsesor(Request $request): JsonResponse
    {
        $data = $request->validate(['codigo_asesor' => ['required', 'string', 'max:20']]);
        $acreditado = $request->user();

        if ($acreditado->asesor_id) {
            $nombre = $acreditado->asesor?->name ?? 'un asesor';
            return response()->json(['message' => "Ya tienes asesor asignado ({$nombre}). Si necesitas cambiarlo, pídeselo a tu asesor."], 422);
        }

        $codigoQr = CodigoQr::buscarActivo($data['codigo_asesor']);
        if (! $codigoQr) {
            throw ValidationException::withMessages([
                'codigo_asesor' => 'Ese código de asesor no existe o ya no está activo. Revísalo.',
            ]);
        }

        app(AsignacionAsesorQr::class)->ligarAcreditado($acreditado, $codigoQr, 'codigo_manual');

        return response()->json([
            'message'    => "Listo. Tu asesor será {$codigoQr->asesor?->name}.",
            'acreditado' => $acreditado->fresh()->toApiArray(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/login
    // ─────────────────────────────────────────────────────────────────────────
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $acreditado = Acreditado::where('email', $data['email'])->first();

        if (! $acreditado || ! Hash::check($data['password'], $acreditado->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        if (! $acreditado->activo) {
            return response()->json(['message' => 'Tu cuenta está desactivada. Contacta a tu asesor.'], 403);
        }

        // Intentar vincular expediente si aún no está vinculado
        if ($acreditado->curp && ! $acreditado->expedientes()->exists()) {
            $acreditado->vincularExpedientePorCurp();
        }

        $token = $acreditado->createToken('acreditado-app')->plainTextToken;

        return response()->json([
            'token'      => $token,
            'acreditado' => $acreditado->fresh()->toApiArray(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/logout
    // ─────────────────────────────────────────────────────────────────────────
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/biometric-token
    //
    // Token independiente para Face ID / huella. logout() solo borra
    // currentAccessToken(), así que con un nombre distinto al de sesión
    // ('acreditado-app'), cerrar sesión no invalida la biometría.
    // ─────────────────────────────────────────────────────────────────────────
    public function biometricToken(Request $request): JsonResponse
    {
        $acreditado = $request->user();
        $acreditado->tokens()->where('name', 'acreditado-app-biometric')->delete();
        $token = $acreditado->createToken('acreditado-app-biometric')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /v1/acreditado/auth/biometric-token
    // ─────────────────────────────────────────────────────────────────────────
    public function revokeBiometricToken(Request $request): JsonResponse
    {
        $request->user()->tokens()->where('name', 'acreditado-app-biometric')->delete();

        return response()->json(['message' => 'Credencial de biometría eliminada.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/session-from-biometric
    //
    // Canjea el token de biometría por uno de sesión normal ('acreditado-app')
    // — logout() solo revoca el de sesión, así que el de biometría sobrevive.
    // ─────────────────────────────────────────────────────────────────────────
    public function sessionFromBiometric(Request $request): JsonResponse
    {
        $acreditado = $request->user();

        $acreditado->tokens()->where('name', 'acreditado-app')->delete();
        $token = $acreditado->createToken('acreditado-app')->plainTextToken;

        return response()->json([
            'token'      => $token,
            'acreditado' => $acreditado->fresh()->toApiArray(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/acreditado/auth/me
    // ─────────────────────────────────────────────────────────────────────────
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->toApiArray()]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /v1/acreditado/auth/perfil
    // ─────────────────────────────────────────────────────────────────────────
    public function updatePerfil(Request $request): JsonResponse
    {
        $acreditado = $request->user();

        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'nss'      => ['sometimes', 'nullable', 'string', 'max:15'],
            'rfc'      => ['sometimes', 'nullable', 'string', 'max:13'],
            'curp'     => ['sometimes', 'nullable', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{2}[A-Z]{3}[A-Z0-9]\d$/i'],
        ]);

        // Si actualizó el CURP e intentar vincular expediente
        $curpCambio = isset($data['curp']) && $data['curp'] !== $acreditado->curp;
        if (isset($data['curp'])) {
            $data['curp'] = strtoupper($data['curp']);
        }

        $acreditado->update($data);

        if ($curpCambio && $acreditado->curp) {
            $acreditado->vincularExpedientePorCurp();
        }

        return response()->json([
            'message'    => 'Perfil actualizado.',
            'acreditado' => $acreditado->fresh()->toApiArray(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/perfil/foto
    // ─────────────────────────────────────────────────────────────────────────
    public function subirFoto(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic', 'max:8192'],
        ]);

        $acreditado = $request->user();

        if ($acreditado->foto_perfil) {
            Storage::disk('local')->delete($acreditado->foto_perfil);
        }

        $file = $request->file('foto');
        $nombre = 'acreditados/' . $acreditado->id . '/foto_' . time() . '.' . $file->getClientOriginalExtension();
        Storage::disk('local')->putFileAs('', $file, $nombre);

        $acreditado->update(['foto_perfil' => $nombre]);

        return response()->json([
            'message'        => 'Foto actualizada.',
            'foto_perfil_url' => $acreditado->fresh()->foto_perfil_url,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/forgot-password
    // ─────────────────────────────────────────────────────────────────────────
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('acreditados')->sendResetLink(
            $request->only('email')
        );

        // Siempre responder OK para no revelar si el email existe
        return response()->json([
            'message' => 'Si tu correo está registrado, recibirás un enlace para restablecer tu contraseña.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/acreditado/{acreditado}/foto-perfil  (URL firmada pública)
    // ─────────────────────────────────────────────────────────────────────────
    public function verFotoPerfil(Request $request, Acreditado $acreditado)
    {
        abort_unless($request->hasValidSignature(), 403);

        if (! $acreditado->foto_perfil || ! Storage::disk('local')->exists($acreditado->foto_perfil)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($acreditado->foto_perfil),
            ['Cache-Control' => 'private, max-age=3600']
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/acreditado/auth/solicitar-cancelacion
    //
    // Requerido por Apple App Store y Google Play Store:
    // toda app con cuentas debe permitir al usuario solicitar la eliminación.
    //
    // Lo que hace:
    // - Marca la cuenta como activo = false
    // - Revoca todos los tokens (cierra sesión en todos los dispositivos)
    // - Notifica al super_admin para que procese la solicitud
    //
    // Los datos del expediente se conservan por obligación legal (5 años).
    // El super_admin puede reactivar si fue un error.
    // ─────────────────────────────────────────────────────────────────────────
    public function solicitarCancelacion(Request $request): JsonResponse
    {
        $acreditado = $request->user();

        // Desactivar la cuenta
        $acreditado->update(['activo' => false]);

        // Revocar todos los tokens de Sanctum
        $acreditado->tokens()->delete();

        // Notificar a super_admins
        \App\Models\User::role('super_admin')->get()->each(function ($admin) use ($acreditado) {
            $admin->notify(new \App\Notifications\CuentaCancelada($acreditado));
        });

        return response()->json([
            'message' => 'Tu solicitud fue recibida. Tu cuenta ha sido desactivada y tus datos serán eliminados en los próximos días hábiles conforme a nuestra política de privacidad.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /v1/acreditado/auth/password
    // Cambiar contraseña estando autenticado
    // ─────────────────────────────────────────────────────────────────────────
    public function cambiarPassword(Request $request): JsonResponse
    {
        $acreditado = $request->user();

        $request->validate([
            'password_actual' => ['required', 'string'],
            'password'        => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ]);

        if (! Hash::check($request->password_actual, $acreditado->password)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta.'], 422);
        }

        $acreditado->update(['password' => $request->password]);

        // Revocar todos los tokens excepto el actual — obliga re-login en otros dispositivos
        $acreditado->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
