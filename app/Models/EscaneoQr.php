<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un escaneo de un QR de asesor: se registra antes de redirigir a la tienda. */
class EscaneoQr extends Model
{
    protected $table = 'escaneos_qr';

    public $timestamps = false;

    protected $fillable = ['codigo_qr_id', 'plataforma', 'ip', 'user_agent', 'created_at', 'atribuido_at', 'atribucion_tipo'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'atribuido_at' => 'datetime'];
    }

    public function codigoQr(): BelongsTo
    {
        return $this->belongsTo(CodigoQr::class, 'codigo_qr_id');
    }

    /**
     * Versión mayor del sistema que declara el User-Agent del navegador que
     * abrió la liga ("iPhone OS 17_5" → 17, "Android 13" → 13). null si no viene.
     */
    public function versionSoMayor(): ?int
    {
        $ua = (string) $this->user_agent;

        if (preg_match('/(?:iPhone|CPU) OS (\d+)_/i', $ua, $m)) return (int) $m[1];
        if (preg_match('/Android (\d+)/i', $ua, $m))           return (int) $m[1];

        return null;
    }

    /** ¿El User-Agent menciona este modelo? (Chrome Android incluye "SM-A135M", Safari iOS no trae modelo) */
    public function coincideModelo(?string $modelo): bool
    {
        $modelo = trim((string) $modelo);
        if ($modelo === '') return false;

        return stripos((string) $this->user_agent, $modelo) !== false;
    }

    /** android | ios | otro, a partir del User-Agent. */
    public static function plataformaDesde(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        if (str_contains($ua, 'android')) return 'android';
        if (preg_match('/iphone|ipad|ipod/', $ua)) return 'ios';

        return 'otro';
    }
}
