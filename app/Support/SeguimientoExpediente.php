<?php

namespace App\Support;

/**
 * Los 7 pasos del trámite que ven el acreditado y el asesor. Son fijos e
 * iguales para todos los tipos de trámite; avanzan solos con los eventos del
 * sistema (salvo dos que marca el super admin con un botón).
 *
 * El texto que recibe el acreditado al llegar a cada paso se edita en el panel
 * (Panel → App móvil); aquí va el texto inicial.
 */
class SeguimientoExpediente
{
    public const VALIDACION_DOCUMENTOS = 'validacion_documentos';
    public const TRAMITES_EN_GESTION   = 'tramites_en_gestion';
    public const TRAMITES_GESTIONADOS  = 'tramites_gestionados';
    public const EN_UNIDAD_VALUACION   = 'en_unidad_valuacion';
    public const RECURSO_EN_GESTION    = 'recurso_en_gestion';
    public const FIRMA                 = 'firma';
    public const CREDITO_ENTREGADO     = 'credito_entregado';

    /** Pasos que el super admin marca con un botón (no tienen evento en el sistema). */
    public const MANUALES = [self::EN_UNIDAD_VALUACION, self::FIRMA];

    public const PASOS = [
        self::VALIDACION_DOCUMENTOS => [
            'nombre'  => 'Validación de documentos',
            'mensaje' => 'Tu expediente está abierto. Sube tus documentos desde la app; un administrador los revisará y te avisaremos si falta alguno.',
        ],
        self::TRAMITES_EN_GESTION => [
            'nombre'  => 'Trámites en gestión',
            'mensaje' => 'Todos tus documentos fueron validados. Ya estamos gestionando tus trámites; no necesitas hacer nada por ahora.',
        ],
        self::TRAMITES_GESTIONADOS => [
            'nombre'  => 'Trámites gestionados',
            'mensaje' => 'Tus trámites quedaron gestionados. El siguiente paso es la unidad de valuación.',
        ],
        self::EN_UNIDAD_VALUACION => [
            'nombre'  => 'En unidad de valuación',
            'mensaje' => 'Tu expediente ya está en la unidad de valuación. Te avisaremos en cuanto tengamos tu CUV.',
        ],
        self::RECURSO_EN_GESTION => [
            'nombre'  => 'Recurso en gestión',
            'mensaje' => 'Ya tenemos tu CUV. Ahora el recurso está en gestión. Abre tu cuenta en Coppel o en tu banco para recibir el dinero.',
        ],
        self::FIRMA => [
            'nombre'  => 'Firma',
            'mensaje' => 'Llegó el momento de firmar. Tu asesor te indicará lugar, fecha y qué llevar.',
        ],
        self::CREDITO_ENTREGADO => [
            'nombre'  => 'Crédito entregado',
            'mensaje' => '¡Felicidades! Tu crédito fue entregado. Gracias por confiar en nosotros.',
        ],
    ];

    public static function orden(string $paso): int
    {
        $i = array_search($paso, array_keys(self::PASOS), true);
        return $i === false ? 0 : $i + 1;
    }

    public static function nombre(string $paso): string
    {
        return self::PASOS[$paso]['nombre'] ?? $paso;
    }

    /** Texto para el acreditado: el editado en el panel o el inicial. */
    public static function mensaje(string $paso): string
    {
        $editado = (string) setting("seguimiento_msg_{$paso}", '');
        return trim($editado) !== '' ? $editado : (self::PASOS[$paso]['mensaje'] ?? '');
    }

    public static function total(): int
    {
        return count(self::PASOS);
    }

    public static function esValido(string $paso): bool
    {
        return array_key_exists($paso, self::PASOS);
    }
}
