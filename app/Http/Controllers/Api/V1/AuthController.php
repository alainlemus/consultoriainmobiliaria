<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    private function userPayload(User $user): array
    {
        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'telefono'        => $user->telefono,
            'banco'           => $user->banco,
            'clabe'           => $user->clabe,
            'foto_perfil_url' => $user->foto_perfil_url,
            'roles'           => $user->getRoleNames(),
        ];
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (! $user->activo) {
            return response()->json(['message' => 'Cuenta inactiva.'], 403);
        }

        $tokenName = $request->input('device_name', 'app-movil');
        $user->tokens()->where('name', $tokenName)->delete();

        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->userPayload($request->user()),
        ]);
    }

    public function updatePerfil(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:20'],
            'banco'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'clabe'    => ['sometimes', 'nullable', 'string', 'max:18'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado.',
            'data'    => $this->userPayload($user->fresh()),
        ]);
    }

    public function subirFotoPerfil(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $user = $request->user();

        // Eliminar foto anterior si existe
        if ($user->foto_perfil) {
            Storage::disk('local')->delete($user->foto_perfil);
        }

        $path = $request->file('foto')->store("fotos_perfil/{$user->id}", 'local');

        $user->update(['foto_perfil' => $path]);

        return response()->json([
            'message'         => 'Foto de perfil actualizada.',
            'foto_perfil_url' => $user->fresh()->foto_perfil_url,
        ]);
    }

    public function verFotoPerfil(Request $request, User $user)
    {
        abort_unless($request->hasValidSignature(), 403);

        if (! $user->foto_perfil || ! Storage::disk('local')->exists($user->foto_perfil)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($user->foto_perfil),
            ['Cache-Control' => 'private, max-age=3600']
        );
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Te enviamos un enlace para restablecer tu contraseña.']);
        }

        // No revelar si el correo existe o no (seguridad)
        return response()->json(['message' => 'Si ese correo está registrado, recibirás un enlace en breve.']);
    }

    /**
     * GET /api/v1/asesores
     * Lista de asesores activos para el filtro en la app.
     * Solo accesible por super_admin.
     */
    public function asesores(Request $request): JsonResponse
    {
        if (! $request->user()?->hasRole('super_admin')) {
            return response()->json(['data' => []]);
        }

        $asesores = User::role('asesor')
            ->where('activo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]);

        return response()->json(['data' => $asesores]);
    }
}
