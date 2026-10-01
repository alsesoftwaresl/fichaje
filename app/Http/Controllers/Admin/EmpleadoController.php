<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class EmpleadoController extends Controller
{
    // DNI: 8 dígitos + letra. NIE: X/Y/Z + 7 dígitos + letra. No se valida la
    // letra de control (requeriría el algoritmo completo); solo el formato,
    // para detectar errores de tecleo obvios.
    protected const REGEX_DNI_NIE = '/^(\d{8}|[XYZxyz]\d{7})[A-Za-z]$/';

    public function index(): View
    {
        $empleados = User::deEmpresa(Auth::user()->empresa_id)
            ->orderBy('name')
            ->paginate(30);

        $empresa = Auth::user()->empresa;

        return view('admin.empleados.index', compact('empleados', 'empresa'));
    }

    public function create(): View
    {
        return view('admin.empleados.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dni_nie' => ['required', 'string', 'max:20', 'regex:'.self::REGEX_DNI_NIE, 'unique:users,dni_nie'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            ...$this->reglasHorario(),
        ]);

        $empleado = User::create([
            'empresa_id' => Auth::user()->empresa_id,
            'name' => $data['name'],
            'dni_nie' => $data['dni_nie'],
            'email' => $data['email'] ?? null,
            'rol' => 'empleado',
            'activo' => true,
            // Sin contraseña no puede entrar a la web, pero sí fichar por
            // PIN en el kiosco — la mayoría del personal no necesita más.
            'password' => $data['password'] ?? Hash::make(Str::random(32)),
            ...$this->datosHorario($data),
        ]);

        $pin = $empleado->generarNuevoPin();

        AuditLog::registrar('empleado_creado', $empleado);
        $this->sincronizarFacturacion();

        return redirect()->route('admin.empleados.index')
            ->with('status', 'Empleado dado de alta.')
            ->with('pin_generado', ['nombre' => $empleado->name, 'pin' => $pin]);
    }

    public function edit(User $empleado): View
    {
        $this->autorizarMismaEmpresa($empleado);

        return view('admin.empleados.edit', compact('empleado'));
    }

    public function update(Request $request, User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dni_nie' => ['required', 'string', 'max:20', 'regex:'.self::REGEX_DNI_NIE, 'unique:users,dni_nie,'.$empleado->id],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,'.$empleado->id],
            // Opcional: en blanco deja la contraseña actual sin tocar. Es la
            // forma de darle acceso a la web más adelante a un empleado que
            // se dio de alta solo con PIN para el kiosco.
            'password' => ['nullable', 'confirmed', Password::defaults()],
            ...$this->reglasHorario(),
        ]);

        $empleado->update([
            'name' => $data['name'],
            'dni_nie' => $data['dni_nie'],
            'email' => $data['email'] ?? null,
            ...(isset($data['password']) ? ['password' => $data['password']] : []),
            ...$this->datosHorario($data),
        ]);

        AuditLog::registrar('empleado_editado', $empleado);

        return redirect()->route('admin.empleados.index')->with('status', 'Empleado actualizado.');
    }

    public function regenerarPin(User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);

        $pin = $empleado->generarNuevoPin();
        AuditLog::registrar('pin_regenerado', $empleado);

        return redirect()->route('admin.empleados.index')
            ->with('pin_generado', ['nombre' => $empleado->name, 'pin' => $pin]);
    }

    public function activar(User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);

        $empleado->update(['activo' => true]);
        AuditLog::registrar('empleado_activado', $empleado);
        $this->sincronizarFacturacion();

        return back()->with('status', 'Empleado activado.');
    }

    public function desactivar(User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);

        $empleado->update(['activo' => false]);
        AuditLog::registrar('empleado_desactivado', $empleado);
        $this->sincronizarFacturacion();

        return back()->with('status', 'Empleado desactivado.');
    }

    protected function autorizarMismaEmpresa(User $empleado): void
    {
        abort_unless($empleado->empresa_id === Auth::user()->empresa_id, 404);
    }

    /**
     * Reglas de validación del horario esperado. Es opcional por completo:
     * un empleado sin horario simplemente no genera incidencias de
     * retraso/horas de más.
     */
    protected function reglasHorario(): array
    {
        return [
            'hora_entrada_esperada' => ['nullable', 'date_format:H:i'],
            'hora_salida_esperada' => ['nullable', 'date_format:H:i', 'after:hora_entrada_esperada'],
            'dias_laborables' => ['nullable', 'array'],
            'dias_laborables.*' => ['integer', 'between:1,7'],
        ];
    }

    protected function datosHorario(array $data): array
    {
        return [
            // El input type="time" manda "H:i" (sin segundos); se normaliza a
            // "H:i:s" para que el formato guardado sea el mismo pase lo que
            // pase por el motor de base de datos (SQLite no normaliza TIME
            // como MySQL).
            'hora_entrada_esperada' => isset($data['hora_entrada_esperada'])
                ? $data['hora_entrada_esperada'].':00'
                : null,
            'hora_salida_esperada' => isset($data['hora_salida_esperada'])
                ? $data['hora_salida_esperada'].':00'
                : null,
            // Los checkboxes llegan como strings ("1","2"...) — se guardan
            // como enteros para que la comparación estricta en
            // User::trabajaEnDia() funcione.
            'dias_laborables' => ! empty($data['dias_laborables'])
                ? array_values(array_map('intval', $data['dias_laborables']))
                : null,
        ];
    }

    /**
     * Actualiza la cantidad de "empleado extra" en Stripe tras un alta/baja.
     * Si Stripe no responde, no debe tumbar la acción principal (el alta/baja
     * ya se guardó bien) — se queda desincronizado hasta el próximo cambio
     * o la siguiente factura, que Stripe recalcula igualmente.
     */
    protected function sincronizarFacturacion(): void
    {
        try {
            Auth::user()->empresa->sincronizarCantidadSuscripcion();
        } catch (ApiErrorException $e) {
            Log::warning('No se pudo sincronizar la cantidad de la suscripción con Stripe: '.$e->getMessage());
        }
    }
}
