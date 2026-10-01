<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class Tenant
{
    protected static ?int $empresaId = null;

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

    public static function check(): bool
    {
        return static::id() !== null;
    }

    public static function clear(): void
    {
        static::$empresaId = null;
        static::$explicit = false;
    }
}
