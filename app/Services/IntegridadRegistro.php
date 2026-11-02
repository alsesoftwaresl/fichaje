<?php

namespace App\Services;

use App\Models\Fichaje;
use App\Models\FichajeCorreccion;

/**
 * Sello de integridad del registro de jornada (art. 34.9 ET: el registro tiene que ser
 * fiable y no manipulable).
 *
 * Cada fichaje guarda una "huella": un HMAC-SHA256 de sus datos legales (empleado, tipo,
 * hora, origen, quién lo añadió, motivo, IP) y de la huella del fichaje anterior del mismo
 * empleado. Las correcciones hacen lo mismo encadenadas por empresa. Así:
 *
 * - Si alguien cambia un dato directamente en la base de datos, la huella deja de cuadrar.
 * - Si alguien borra un fichaje, el siguiente deja de cuadrar (apuntaba al borrado).
 * - Sin la APP_KEY no se puede recalcular una huella válida, así que tampoco se puede
 *   "arreglar" la cadena después de manipularla.
 *
 * verificar() recorre las cadenas y devuelve lo que no cuadra; lo usan la pantalla de
 * Fichajes del admin y el comando "registro:verificar".
 */
class IntegridadRegistro
{
    /**
     * Huella de un fichaje a partir de sus datos (como array, para poder usarla igual con
     * modelos y con filas de la base de datos en la migración que sella los antiguos).
     *
     * @param  array{empresa_id: mixed, user_id: mixed, tipo: string, fecha_hora: string, origen: ?string, registrado_por: mixed, motivo: ?string, ip_address: ?string}  $datos
     */
    public static function huellaFichaje(array $datos, ?string $anterior): string
    {
        return static::firmar([
            'fichaje',
            $datos['empresa_id'],
            $datos['user_id'],
            $datos['tipo'],
            $datos['fecha_hora'],
            $datos['origen'],
            $datos['registrado_por'],
            $datos['motivo'],
            $datos['ip_address'],
            $anterior,
        ]);
    }

    /**
     * Huella de una corrección (hora nueva, motivo y autor), encadenada a la anterior de la
     * misma empresa.
     *
     * @param  array{empresa_id: mixed, fichaje_original_id: mixed, fecha_hora_corregida: string, motivo: ?string, corregido_por: mixed}  $datos
     */
    public static function huellaCorreccion(array $datos, ?string $anterior): string
    {
        return static::firmar([
            'correccion',
            $datos['empresa_id'],
            $datos['fichaje_original_id'],
            $datos['fecha_hora_corregida'],
            $datos['motivo'],
            $datos['corregido_por'],
            $anterior,
        ]);
    }

    /**
     * Pone la huella a un fichaje que se va a crear. Se llama desde el evento "creating"
     * del modelo; quien crea fichajes de un empleado debe tener su bloqueo ("fichar:{id}")
     * para que dos fichajes simultáneos no partan de la misma huella anterior.
     */
    public static function sellarFichaje(Fichaje $fichaje): void
    {
        $anterior = Fichaje::withoutGlobalScopes()
            ->where('user_id', $fichaje->user_id)
            ->orderByDesc('id')
            ->value('huella');

        $fichaje->huella = static::huellaFichaje(static::datosFichaje($fichaje), $anterior);
    }

    /**
     * Pone la huella a una corrección que se va a crear (cadena por empresa).
     */
    public static function sellarCorreccion(FichajeCorreccion $correccion): void
    {
        $anterior = FichajeCorreccion::withoutGlobalScopes()
            ->where('empresa_id', $correccion->empresa_id)
            ->orderByDesc('id')
            ->value('huella');

        $correccion->huella = static::huellaCorreccion(static::datosCorreccion($correccion), $anterior);
    }

    /**
     * Comprueba todas las cadenas de una empresa.
     *
     * @return array{fichajes: int, correcciones: int, problemas: list<array{tipo: string, id: int, fecha: string, detalle: string}>}
     */
    public static function verificar(int $empresaId): array
    {
        $problemas = [];
        $fichajes = 0;
        $anteriorPorEmpleado = [];

        foreach (Fichaje::withoutGlobalScopes()->where('empresa_id', $empresaId)->orderBy('id')->lazyById(500) as $fichaje) {
            $fichajes++;
            $anterior = $anteriorPorEmpleado[$fichaje->user_id] ?? null;

            if (! hash_equals(static::huellaFichaje(static::datosFichaje($fichaje), $anterior), (string) $fichaje->huella)) {
                $problemas[] = [
                    'tipo' => 'fichaje',
                    'id' => $fichaje->id,
                    'fecha' => $fichaje->fecha_hora->format('d/m/Y H:i'),
                    'detalle' => 'Fichaje modificado fuera de la aplicación, o falta el fichaje anterior de este empleado.',
                ];
            }

            $anteriorPorEmpleado[$fichaje->user_id] = $fichaje->huella;
        }

        $correcciones = 0;
        $anterior = null;

        foreach (FichajeCorreccion::withoutGlobalScopes()->where('empresa_id', $empresaId)->orderBy('id')->lazyById(500) as $correccion) {
            $correcciones++;

            if (! hash_equals(static::huellaCorreccion(static::datosCorreccion($correccion), $anterior), (string) $correccion->huella)) {
                $problemas[] = [
                    'tipo' => 'corrección',
                    'id' => $correccion->id,
                    'fecha' => $correccion->fecha_hora_corregida->format('d/m/Y H:i'),
                    'detalle' => 'Corrección modificada fuera de la aplicación, o falta la corrección anterior.',
                ];
            }

            $anterior = $correccion->huella;
        }

        return ['fichajes' => $fichajes, 'correcciones' => $correcciones, 'problemas' => $problemas];
    }

    /**
     * Datos de un fichaje tal y como se guardan (la hora sin fracciones de segundo, igual
     * que queda en la base de datos).
     */
    protected static function datosFichaje(Fichaje $f): array
    {
        return [
            'empresa_id' => $f->empresa_id,
            'user_id' => $f->user_id,
            'tipo' => $f->tipo,
            'fecha_hora' => $f->fecha_hora->format('Y-m-d H:i:s'),
            'origen' => $f->origen ?? 'web',
            'registrado_por' => $f->registrado_por,
            'motivo' => $f->motivo,
            'ip_address' => $f->ip_address,
        ];
    }

    /**
     * Datos de una corrección tal y como se guardan.
     */
    protected static function datosCorreccion(FichajeCorreccion $c): array
    {
        return [
            'empresa_id' => $c->empresa_id,
            'fichaje_original_id' => $c->fichaje_original_id,
            'fecha_hora_corregida' => $c->fecha_hora_corregida->format('Y-m-d H:i:s'),
            'motivo' => $c->motivo,
            'corregido_por' => $c->corregido_por,
        ];
    }

    /**
     * HMAC-SHA256 de los campos con una clave derivada de APP_KEY (propia de este uso, para
     * no reutilizar la clave de cifrado tal cual). Los campos se separan con un carácter de
     * control que no aparece en los datos, así "1|23" y "12|3" no dan la misma huella.
     */
    protected static function firmar(array $campos): string
    {
        $clave = hash_hmac('sha256', 'achrono:integridad-registro', (string) config('app.key'));

        return hash_hmac('sha256', implode("\x1F", array_map(fn ($v) => (string) $v, $campos)), $clave);
    }
}
