<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    // Nota: User NO usa el scope global de tenant (BelongsToTenant). super_admin
    // necesita poder crear/ver usuarios de cualquier empresa (p.ej. al dar de alta
    // una empresa nueva con su primer admin_empresa), así que empresa_id se asigna
    // explícitamente en cada flujo. Los controladores de admin_empresa deben
    // filtrar siempre con scopeDeEmpresa() o Auth::user()->empresa_id.

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'empresa_id',
        'name',
        'email',
        'dni_nie',
        'rol',
        'activo',
        'password',
        'hora_entrada_esperada',
        'hora_salida_esperada',
        'dias_laborables',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'dias_laborables' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Se guarda siempre en mayúsculas: es el identificador que se usa para
     * iniciar sesión (junto con el email), y así "12345678z" y "12345678Z"
     * se tratan como el mismo DNI tanto al comparar como al validar que sea
     * único.
     */
    protected function dniNie(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Str::upper(trim($value)) : null,
        );
    }

    public function esSuperAdmin(): bool
    {
        return $this->rol === 'super_admin';
    }

    public function esAdminEmpresa(): bool
    {
        return $this->rol === 'admin_empresa';
    }

    public function esEmpleado(): bool
    {
        return $this->rol === 'empleado';
    }

    public function scopeDeEmpresa(Builder $query, int $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Genera y guarda un PIN nuevo de 6 dígitos para fichar en el kiosco.
     * Se devuelve en claro UNA vez (para mostrárselo al admin) — no se
     * puede recuperar después, solo regenerar. Se guarda como un hash HMAC
     * determinista (no bcrypt) para poder buscarlo por igualdad indexada sin
     * tener que comparar contra todos los empleados de la empresa.
     */
    public function generarNuevoPin(): string
    {
        do {
            $pin = (string) random_int(100000, 999999);
            $hash = static::hashPin($pin);
        } while (static::where('empresa_id', $this->empresa_id)->where('pin_hash', $hash)->exists());

        $this->pin_hash = $hash;
        $this->save();

        return $pin;
    }

    public static function hashPin(string $pin): string
    {
        return hash_hmac('sha256', $pin, config('app.key'));
    }

    public static function buscarPorPin(int $empresaId, string $pin): ?self
    {
        return static::where('empresa_id', $empresaId)
            ->where('pin_hash', static::hashPin($pin))
            ->where('activo', true)
            ->first();
    }

    public function ausencias(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ausencia::class);
    }

    public function tieneHorario(): bool
    {
        return $this->hora_entrada_esperada !== null
            && $this->hora_salida_esperada !== null
            && ! empty($this->dias_laborables);
    }

    public function trabajaEnDia(\Illuminate\Support\Carbon $fecha): bool
    {
        return $this->tieneHorario() && in_array($fecha->dayOfWeekIso, $this->dias_laborables ?? [], true);
    }
}
