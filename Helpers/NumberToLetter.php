<?php

declare(strict_types=1);

namespace Helpers;

class NumberToLetter
{
    private static array $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    private static array $decenas = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    private static array $decenas_puras = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    private static array $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    public static function convert(float $number): string
    {
        $integer = (int)$number;
        $decimals = str_pad((string)round(($number - $integer) * 100), 2, '0', STR_PAD_LEFT);

        if ($integer === 0) {
            $letras = 'CERO';
        } else {
            $letras = self::procesar($integer);
        }

        // Estándar Bancario Mexicano
        return sprintf('%s PESOS %s/100 M.N.', $letras, $decimals);
    }

    private static function procesar(int $n): string
    {
        if ($n >= 1000000) {
            $millones = (int)($n / 1000000);
            $resto = $n % 1000000;
            $txt = ($millones === 1) ? 'UN MILLON' : self::convertirBloque($millones) . ' MILLONES';
            return trim($txt . ' ' . self::procesar($resto));
        }

        if ($n >= 1000) {
            $miles = (int)($n / 1000);
            $resto = $n % 1000;
            $txt = ($miles === 1) ? 'MIL' : self::convertirBloque($miles) . ' MIL';
            return trim($txt . ' ' . self::procesar($resto));
        }

        return self::convertirBloque($n);
    }

    private static function convertirBloque(int $n): string
    {
        if ($n === 100) return 'CIEN';
        
        $output = '';
        $c = (int)($n / 100);
        $d = (int)(($n % 100) / 10);
        $u = $n % 10;

        // Centenas
        $output .= self::$centenas[$c] . ' ';

        // Decenas
        if ($d === 1) {
            $output .= self::$decenas[$u];
        } else {
            $output .= self::$decenas_puras[$d];
            if ($d > 2 && $u > 0) {
                $output .= ' Y ';
            } elseif ($d === 2 && $u > 0) {
                $output = str_replace('VEINTE', 'VEINTI', $output);
            }
            $output .= self::$unidades[$u];
        }

        return trim($output);
    }
}