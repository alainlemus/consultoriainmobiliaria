<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Acreditado\AuthController as AcreditadoAuthController;
use App\Http\Controllers\Controller;
use App\Models\Acreditado;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * POST /v1/auth/entrar — login único de la app.
 *
 * Asesores, gestores y super_admin viven en `users`; los acreditados, en
 * `acreditados`. La app ya no pregunta «¿cómo quieres entrar?»: manda correo y
 * contraseña aquí y la respuesta dice qué tipo de cuenta es (`tipo`), con el
 * mismo cuerpo que los logins de siempre (que siguen existiendo para las
 * versiones anteriores de la app).
 *
 * Si el mismo correo existe en las dos tablas, gana la cuenta cuya contraseña
 * coincide; si coinciden las dos, la de empleado.
 */
class EntrarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $email = strtolower(trim($data['email']));
        $request->merge(['email' => $email]);

        $user = User::where('email', $email)->first();
        if ($user && Hash::check($data['password'], $user->password)) {
            return $this->conTipo(app(AuthController::class)->login($request), 'staff');
        }

        $acreditado = Acreditado::where('email', $email)->first();
        if ($acreditado && Hash::check($data['password'], $acreditado->password)) {
            return $this->conTipo(app(AcreditadoAuthController::class)->login($request), 'acreditado');
        }

        throw ValidationException::withMessages(['email' => ['Las credenciales son incorrectas.']]);
    }

    /** Agrega `tipo` solo a las respuestas exitosas (una cuenta inactiva regresa su 403 tal cual). */
    private function conTipo(JsonResponse $res, string $tipo): JsonResponse
    {
        if ($res->isSuccessful()) {
            $res->setData(['tipo' => $tipo] + (array) $res->getData(true));
        }

        return $res;
    }
}
