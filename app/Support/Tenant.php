<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Guarda cuál es la empresa (tenant) de la petición en curso. Se fija a mano en el
 * kiosco y en tests; el resto del tiempo se deduce del usuario con sesión.
 */
class Tenant
{
    /**
     * Empresa fijada a mano (si la hay).
     */
    protected static ?int $empresaId = null;

    /**
     * true cuando la empresa se fijó a mano en vez de deducirla del usuario.
     */
    protected static bool $explicit = false;

    /**
     * Fija el tenant explícitamente (usado en tests/seeders sin request HTTP).
     */
    public static function set(?int $empresaId): void
    {
        static::$empresaId = $empresaId;
        static::$explicit = true;
    }

    /**
     * Si no se ha fijado explícitamente, cae al empresa_id del usuario
     * autenticado. Esto es necesario porque Laravel resuelve el
     * route-model-binding (SubstituteBindings) ANTES que nuestro middleware
     * IdentifyTenant, así que el scope de tenant no puede depender solo de
     * que ese middleware ya se haya ejecutado.
     */
    public static function id(): ?int
    {
        if (static::$explicit) {
            return static::$empresaId;
        }

        return Auth::user()?->empresa_id;
    }

    /**
     * true si hay una empresa identificada.
     */
    public static function check(): bool
    {
        return static::id() !== null;
    }

    /**
     * Olvida la empresa fijada (se llama al terminar cada petición).
     */
    public static function clear(): void
    {
        static::$empresaId = null;
        static::$explicit = false;
    }
}
