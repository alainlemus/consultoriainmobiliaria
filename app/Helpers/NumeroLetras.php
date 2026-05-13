<?php

namespace App\Helpers;

class NumeroLetras
{
    private static array $unidades = [
        '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS',
        'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE',
    ];

    private static array $decenas = [
        '', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA',
        'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    private static array $centenas = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    public static function convertir(float $numero): string
    {
        $numero   = abs($numero);
        $entero   = (int) $numero;
        $decimales = round(($numero - $entero) * 100);

        $letras = self::enteroALetras($entero);

        if ($decimales > 0) {
            $letras .= ' CON ' . str_pad((string) $decimales, 2, '0', STR_PAD_LEFT) . '/100';
        }

        return $letras;
    }

    private static function enteroALetras(int $numero): string
    {
        if ($numero === 0) {
            return 'CERO';
        }

        if ($numero === 100) {
            return 'CIEN';
        }

        if ($numero <= 20) {
            return self::$unidades[$numero];
        }

        if ($numero < 100) {
            $decena  = intdiv($numero, 10);
            $unidad  = $numero % 10;
            $texto   = self::$decenas[$decena];
            if ($unidad > 0) {
                // 21–29 → VEINTIUNO, VEINTIDÓS…
                if ($decena === 2) {
                    $veinti = ['', 'VEINTIUNO', 'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO',
                        'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
                    return $veinti[$unidad];
                }
                $texto .= ' Y ' . self::$unidades[$unidad];
            }
            return $texto;
        }

        if ($numero < 1000) {
            $centena = intdiv($numero, 100);
            $resto   = $numero % 100;
            $texto   = self::$centenas[$centena];
            if ($resto > 0) {
                $texto .= ' ' . self::enteroALetras($resto);
            }
            return $texto;
        }

        if ($numero < 2000) {
            $resto = $numero % 1000;
            $texto = 'MIL';
            if ($resto > 0) {
                $texto .= ' ' . self::enteroALetras($resto);
            }
            return $texto;
        }

        if ($numero < 1_000_000) {
            $miles = intdiv($numero, 1000);
            $resto = $numero % 1000;
            $texto = self::enteroALetras($miles) . ' MIL';
            if ($resto > 0) {
                $texto .= ' ' . self::enteroALetras($resto);
            }
            return $texto;
        }

        if ($numero < 2_000_000) {
            $resto = $numero % 1_000_000;
            $texto = 'UN MILLÓN';
            if ($resto > 0) {
                $texto .= ' ' . self::enteroALetras($resto);
            }
            return $texto;
        }

        $millones = intdiv($numero, 1_000_000);
        $resto    = $numero % 1_000_000;
        $texto    = self::enteroALetras($millones) . ' MILLONES';
        if ($resto > 0) {
            $texto .= ' ' . self::enteroALetras($resto);
        }
        return $texto;
    }
}
