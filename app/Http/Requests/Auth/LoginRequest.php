<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validación y autenticación del login con límite de intentos (5 fallos bloquean un
 * rato). El campo "login" acepta email o DNI/NIE.
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Un admin_empresa/super_admin se identifica con su email; un empleado
     * normalmente no tiene uno asignado y usa su DNI/NIE en su lugar. El
     * mismo campo "login" acepta cualquiera de los dos: si contiene "@" se
     * busca por email, si no por dni_nie.
     *
     * Un usuario dado de baja (activo=false) o de una empresa desactivada no
     * puede entrar, sin necesidad de borrar su fila ni sus fichajes.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim((string) $this->string('login'));

        $usuario = str_contains($login, '@')
            ? $this->usuarioPorEmail($login)
            : $this->usuarioPorDni(Str::upper($login));

        if (! $usuario) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        // Como Auth::attempt(): si el coste del cifrado ha subido desde que se guardó la
        // contraseña, se vuelve a cifrar ahora que la tenemos en claro.
        if (config('hashing.rehash_on_login', true)) {
            Auth::getProvider()->rehashPasswordIfRequired($usuario, ['password' => (string) $this->string('password')]);
        }

        Auth::login($usuario, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * El email es único en toda la app.
     */
    protected function usuarioPorEmail(string $email): ?User
    {
        $usuario = User::where('email', $email)->where('activo', true)->first();

        return $usuario && $this->puedeEntrar($usuario) ? $usuario : null;
    }

    /**
     * El DNI/NIE es único dentro de cada empresa, pero la misma persona puede estar en dos.
     * Se queda con la cuenta cuya contraseña coincide; si coinciden varias no se puede
     * saber a cuál quiere entrar y se le pide que use otra contraseña en una de ellas.
     *
     * @throws ValidationException
     */
    protected function usuarioPorDni(string $dni): ?User
    {
        $coinciden = User::where('dni_nie', $dni)
            ->where('activo', true)
            ->get()
            ->filter(fn (User $usuario) => $this->puedeEntrar($usuario));

        if ($coinciden->count() > 1) {
            throw ValidationException::withMessages([
                'login' => 'Tu DNI está dado de alta en varias empresas con la misma contraseña. Pide a una de ellas que te dé una contraseña nueva, o entra con tu email.',
            ]);
        }

        return $coinciden->first();
    }

    /**
     * Contraseña correcta y, si pertenece a una empresa, que esa empresa esté activa.
     */
    protected function puedeEntrar(User $usuario): bool
    {
        return $usuario->password !== null
            && Hash::check((string) $this->string('password'), $usuario->password)
            && ($usuario->empresa_id === null || $usuario->empresa?->activa === true);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
