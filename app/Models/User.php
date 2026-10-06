<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

// MustVerifyEmail es a nivel de clase, pero NO todos los usuarios tienen que
// verificar nada: el middleware "verificado" (RequireEmailVerificado) solo lo
// exige a admin_empresa venidos del registro público. Empleados y admins
// dados de alta a mano por super_admin quedan verificados automáticamente
// (ver AltaEmpresaService) y nunca pasan por ese middleware.
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, MustVerifyEmail;

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
        'email_verified_at',
        'dni_nie',
        'rol',
        'activo',
        'gestiona_nominas',
        'password',
        'debe_cambiar_password',
        'hora_entrada_esperada',
        'hora_salida_esperada',
        'dias_laborables',
        'horario_tramos',
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
            'gestiona_nominas' => 'boolean',
            'debe_cambiar_password' => 'boolean',
            'dias_laborables' => 'array',
            'horario_tramos' => 'array',
        ];
    }

    /**
     * Empresa a la que pertenece (null para el super admin).
     */
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

    /**
     * true si es el administrador de la plataforma.
     */
    public function esSuperAdmin(): bool
    {
        return $this->rol === 'super_admin';
    }

    /**
     * true si es el administrador de una empresa cliente.
     */
    public function esAdminEmpresa(): bool
    {
        return $this->rol === 'admin_empresa';
    }

    /**
     * true si es un empleado normal.
     */
    public function esEmpleado(): bool
    {
        return $this->rol === 'empleado';
    }

    /**
     * Filtra los usuarios de una empresa: User::deEmpresa($id).
     */
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

    /**
     * Contraseña temporal legible (sin 0/O/1/l/I, que se confunden al
     * dictarlas). Se muestra una sola vez al admin y obliga a cambiarla.
     */
    public static function generarPasswordTemporal(int $longitud = 10): string
    {
        $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';

        for ($i = 0; $i < $longitud; $i++) {
            $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return $password;
    }

    /**
     * Huella irreversible del PIN (HMAC con la clave de la app). El PIN en claro nunca se
     * guarda.
     */
    public static function hashPin(string $pin): string
    {
        return hash_hmac('sha256', $pin, config('app.key'));
    }

    /**
     * Busca al empleado activo de la empresa cuyo PIN coincide (lo usa el kiosco).
     */
    public static function buscarPorPin(int $empresaId, string $pin): ?self
    {
        return static::where('empresa_id', $empresaId)
            ->where('pin_hash', static::hashPin($pin))
            ->where('activo', true)
            ->first();
    }

    /**
     * Solicitudes de ausencia del usuario.
     */
    public function ausencias(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ausencia::class);
    }

    /**
     * Nóminas del usuario.
     */
    public function nominas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Nomina::class);
    }

    /**
     * Subir/gestionar nóminas: los admin_empresa siempre, y quien tenga el
     * permiso de contable (users.gestiona_nominas) sin ser admin.
     */
    public function puedeGestionarNominas(): bool
    {
        return $this->empresa_id !== null
            && ($this->esAdminEmpresa() || $this->gestiona_nominas);
    }

    /**
     * Avisos de cita del usuario.
     */
    public function citas():\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cita::class);
    }

    /**
     * Tramos del horario esperado, p. ej. [['entrada' => '09:00', 'salida' => '14:00'], ...].
     * Un turno partido tiene varios; el horario corrido es un único tramo
     * (el de hora_entrada_esperada / hora_salida_esperada). Vacío si no hay
     * horario configurado.
     *
     * @return list<array{entrada: string, salida: string}>
     */
    public function tramos(): array
    {
        if (! empty($this->horario_tramos)) {
            return array_values($this->horario_tramos);
        }

        if ($this->hora_entrada_esperada !== null && $this->hora_salida_esperada !== null) {
            return [[
                'entrada' => substr($this->hora_entrada_esperada, 0, 5),
                'salida' => substr($this->hora_salida_esperada, 0, 5),
            ]];
        }

        return [];
    }

    /**
     * true si tiene tramos de horario y al menos un día laborable.
     */
    public function tieneHorario(): bool
    {
        return $this->tramos() !== [] && ! empty($this->dias_laborables);
    }

    /**
     * true si tiene horario y el día dado de la semana es laborable para él.
     */
    public function trabajaEnDia(\Illuminate\Support\Carbon $fecha): bool
    {
        return $this->tieneHorario() && in_array($fecha->dayOfWeekIso, $this->dias_laborables ?? [], true);
    }
}
