<?php

namespace App\Support;

/**
 * Códigos de un solo uso basados en el tiempo (TOTP, RFC 6238): los que genera una app de
 * autenticación (Google Authenticator, Microsoft Authenticator, Authy, el llavero del
 * móvil...). Implementación propia y pequeña para no depender de otro paquete: HMAC-SHA1
 * sobre el número de intervalos de 30 segundos, 6 cifras.
 */
class Totp
{
    const DIGITOS = 6;

    const SEGUNDOS = 30;

    /** Intervalos de margen a cada lado (relojes del móvil algo adelantados o atrasados). */
    const MARGEN = 1;

    protected const ALFABETO = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Secreto nuevo en base32 (160 bits, lo que recomienda el RFC 4226).
     */
    public static function secreto(): string
    {
        return static::base32(random_bytes(20));
    }

    /**
     * Código del intervalo $paso (por defecto, el actual).
     */
    public static function codigo(string $secreto, ?int $paso = null): string
    {
        $paso ??= static::pasoActual();
        $hash = hash_hmac('sha1', pack('J', $paso), static::desdeBase32($secreto), true);
        $desplazamiento = ord($hash[19]) & 0x0F;
        $numero = (unpack('N', substr($hash, $desplazamiento, 4))[1] & 0x7FFFFFFF) % (10 ** static::DIGITOS);

        return str_pad((string) $numero, static::DIGITOS, '0', STR_PAD_LEFT);
    }

    /**
     * Devuelve el intervalo en que el código es válido (para no aceptar dos veces el mismo
     * código), o null si no vale. Se aceptan espacios entre las cifras.
     */
    public static function verificar(string $secreto, string $codigo): ?int
    {
        $codigo = preg_replace('/\s+/', '', $codigo);

        if (! preg_match('/^\d{'.static::DIGITOS.'}$/', $codigo)) {
            return null;
        }

        $actual = static::pasoActual();

        for ($paso = $actual - static::MARGEN; $paso <= $actual + static::MARGEN; $paso++) {
            if (hash_equals(static::codigo($secreto, $paso), $codigo)) {
                return $paso;
            }
        }

        return null;
    }

    /**
     * Dirección otpauth:// que leen las apps de autenticación (en el QR o al pulsarla en
     * el móvil).
     */
    public static function uri(string $secreto, string $cuenta, string $emisor): string
    {
        return 'otpauth://totp/'.rawurlencode($emisor.':'.$cuenta)
            .'?secret='.$secreto.'&issuer='.rawurlencode($emisor)
            .'&digits='.static::DIGITOS.'&period='.static::SEGUNDOS;
    }

    /**
     * Número de intervalos de 30 s desde 1970 (usa la hora de la app, así las pruebas pueden
     * moverla).
     */
    public static function pasoActual(): int
    {
        return intdiv(now()->getTimestamp(), static::SEGUNDOS);
    }

    /**
     * Bytes a base32 (RFC 4648, sin relleno).
     */
    protected static function base32(string $bytes): string
    {
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(
            fn (string $grupo) => static::ALFABETO[bindec(str_pad($grupo, 5, '0'))],
            str_split($bits, 5)
        ));
    }

    /**
     * Base32 a bytes (ignora espacios, guiones y minúsculas al teclearlo a mano).
     */
    protected static function desdeBase32(string $texto): string
    {
        $bits = '';

        foreach (str_split(strtoupper(preg_replace('/[\s=-]/', '', $texto))) as $letra) {
            $valor = strpos(static::ALFABETO, $letra);
            $bits .= $valor === false ? '' : str_pad(decbin($valor), 5, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn (string $byte) => chr(bindec($byte)), array_filter(
            str_split($bits, 8),
            fn (string $byte) => strlen($byte) === 8
        )));
    }
}
