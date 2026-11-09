<?php

namespace App\Services;

use App\Models\DocumentoExpediente;
use App\Models\Expediente;
use App\Models\User;
use App\Notifications\CuvRegistrada;
use App\Support\SeguimientoExpediente as S;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * CUV (Clave Única de Vivienda): 16 dígitos que entrega la unidad de valuación.
 * La registra el super admin o el gestor con encargo activo, a mano y/o con
 * foto o PDF. Al registrarla el seguimiento pasa a «Recurso en gestión» y se
 * avisa al acreditado, al asesor y a los super admin.
 */
class CuvService
{
    private const DISK = 'local';

    public static function esValida(?string $cuv): bool
    {
        return (bool) preg_match('/^\d{16}$/', (string) $cuv);
    }

    public function registrar(Expediente $exp, string $cuv, ?UploadedFile $archivo, User $por): Expediente
    {
        Expediente::withoutEvents(fn () => $exp->forceFill([
            'cuv'                => $cuv,
            'cuv_registrado_at'  => now(),
            'cuv_registrado_por' => $por->id,
        ])->save());

        if ($archivo) {
            $ext  = strtolower($archivo->getClientOriginalExtension() ?: 'pdf');
            $dir  = "expedientes/{$exp->id}/docs";
            $name = 'cuv_' . now()->format('YmdHis') . '_' . Str::lower(Str::random(5)) . '.' . $ext;
            Storage::disk(self::DISK)->putFileAs($dir, $archivo, $name);

            DocumentoExpediente::create([
                'expediente_id' => $exp->id,
                'subido_por_id' => $por->id,
                'tipo'          => 'CUV',
                'nombre'        => "CUV {$cuv}",
                'seccion'       => 'otros',
                'categoria'     => 'cuv',
                'estado'        => 'recibido',
                'ruta_archivo'  => "{$dir}/{$name}",
                'validado_at'   => now(),
                'validado_por'  => $por->id,
            ]);
        }

        app(SeguimientoExpedienteService::class)->avanzar($exp, S::RECURSO_EN_GESTION, $por);

        $exp->loadMissing('asesor');
        $destinatarios = User::role('super_admin')->where('activo', true)->get();
        if ($exp->asesor) $destinatarios->push($exp->asesor);
        $destinatarios->unique('id')->reject(fn ($u) => $u->id === $por->id)
            ->each(fn (User $u) => $u->notify(new CuvRegistrada($exp->fresh(), $por->name)));

        return $exp->fresh();
    }

    public static function payload(Expediente $exp): ?array
    {
        if (! $exp->cuv) return null;

        return [
            'valor' => $exp->cuv,
            'fecha' => $exp->cuv_registrado_at?->toIso8601String(),
            'por'   => $exp->cuv_registrado_por ? User::find($exp->cuv_registrado_por)?->name : null,
        ];
    }
}
