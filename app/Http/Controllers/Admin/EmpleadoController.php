<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\EstadoEquipoCalculador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Gestión de empleados por el admin de empresa: alta, edición, horario esperado, acceso
 * a la web (contraseña temporal), PIN del kiosco, permiso de contable y
 * activar/desactivar. Al cambiar el número de empleados activos se ajusta la
 * facturación.
 */
class EmpleadoController extends Controller
{
    // DNI: 8 dígitos + letra. NIE: X/Y/Z + 7 dígitos + letra. No se valida la
    // letra de control (requeriría el algoritmo completo); solo el formato,
    // para detectar errores de tecleo obvios.
    protected const REGEX_DNI_NIE = '/^(\d{8}|[XYZxyz]\d{7})[A-Za-z]$/';

    /**
     * Lista de empleados (30 por página) con su estado de hoy: trabajando, vacaciones,
     * baja o fuera.
     */
    public function index(): View
    {
        $empleados = User::deEmpresa(Auth::user()->empresa_id)
            ->orderBy('name')
            ->paginate(30);

        $empresa = Auth::user()->empresa;

        // Estado de hoy (trabajando / vacaciones / baja / fuera) por empleado,
        // el mismo cálculo que el panel. Los desactivados no salen aquí.
        $estados = EstadoEquipoCalculador::delDia($empresa->id)
            ->keyBy(fn (array $item) => $item['empleado']->id);

        return view('admin.empleados.index', compact('empleados', 'empresa', 'estados'));
    }

    /**
     * Formulario de alta de empleado.
     */
    public function create(): View
    {
        return view('admin.empleados.create');
    }

    /**
     * Da de alta un empleado con DNI/NIE, email opcional, horario opcional y acceso web
     * opcional (contraseña propia o temporal). Genera su PIN para el kiosco y lo deja en
     * auditoría.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->comprobarLimiteDeLicencia();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'dni_nie' => ['required', 'string', 'max:20', 'regex:'.self::REGEX_DNI_NIE, 'unique:users,dni_nie'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            ...$this->reglasHorario(),
        ]);

        // Acceso a la web: contraseña que escribe el admin, una temporal
        // generada (marcando "dar acceso") o ninguna — en ese caso solo ficha
        // por PIN en el kiosco. En los dos primeros el empleado tendrá que
        // elegir la suya al entrar por primera vez.
        $passwordTemporal = null;

        if (! empty($data['password'])) {
            $password = $data['password'];
        } elseif ($request->boolean('dar_acceso')) {
            $password = $passwordTemporal = User::generarPasswordTemporal();
        } else {
            $password = Hash::make(Str::random(32));
        }

        $empleado = User::create([
            'empresa_id' => Auth::user()->empresa_id,
            'name' => $data['name'],
            'dni_nie' => $data['dni_nie'],
            'email' => $data['email'] ?? null,
            'rol' => 'empleado',
            'activo' => true,
            'gestiona_nominas' => $request->boolean('gestiona_nominas'),
            'password' => $password,
            'debe_cambiar_password' => ! empty($data['password']) || $passwordTemporal !== null,
            ...$this->datosHorario($data),
        ]);

        $pin = $empleado->generarNuevoPin();

        AuditLog::registrar('empleado_creado', $empleado);
        $this->sincronizarFacturacion();

        $respuesta = redirect()->route('admin.empleados.index')
            ->with('status', 'Empleado dado de alta.')
            ->with('pin_generado', ['nombre' => $empleado->name, 'pin' => $pin]);

        if ($passwordTemporal !== null) {
            $respuesta->with('acceso_generado', $this->datosAcceso($empleado, $passwordTemporal));
        }

        return $respuesta;
    }

    /**
     * Genera una contraseña temporal nueva (o la primera, si solo tenía PIN)
     * para que el empleado entre a la web y la cambie al primer acceso.
     */
    public function generarAcceso(User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);

        $password = User::generarPasswordTemporal();
        $empleado->update(['password' => $password, 'debe_cambiar_password' => true]);

        AuditLog::registrar('acceso_web_generado', $empleado);

        return redirect()->route('admin.empleados.index')
            ->with('acceso_generado', $this->datosAcceso($empleado, $password));
    }

    /**
     * Datos que se enseñan una sola vez al admin tras generar un acceso: nombre, DNI y
     * contraseña temporal.
     */
    protected function datosAcceso(User $empleado, string $password): array
    {
        return ['nombre' => $empleado->name, 'dni' => $empleado->dni_nie, 'password' => $password];
    }

    /**
     * Formulario de edición (solo de empleados de la propia empresa).
     */
    public function edit(User $empleado): View
    {
        $this->autorizarMismaEmpresa($empleado);

        return view('admin.empleados.edit', compact('empleado'));
    }

    /**
     * Guarda los cambios del empleado. Si el admin cambia la contraseña, el empleado
     * tendrá que elegir la suya al volver a entrar.
     */
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
            'gestiona_nominas' => $request->boolean('gestiona_nominas'),
            // Si el admin le cambia la contraseña, el empleado elegirá la suya
            // al volver a entrar.
            ...(isset($data['password']) ? ['password' => $data['password'], 'debe_cambiar_password' => true] : []),
            ...$this->datosHorario($data),
        ]);

        AuditLog::registrar('empleado_editado', $empleado);

        return redirect()->route('admin.empleados.index')->with('status', 'Empleado actualizado.');
    }

    /**
     * Genera un PIN nuevo de 6 dígitos para el kiosco y lo muestra una sola vez.
     */
    public function regenerarPin(User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);

        $pin = $empleado->generarNuevoPin();
        AuditLog::registrar('pin_regenerado', $empleado);

        return redirect()->route('admin.empleados.index')
            ->with('pin_generado', ['nombre' => $empleado->name, 'pin' => $pin]);
    }

    /**
     * Reactiva a un empleado y reajusta la facturación (empleados activos).
     */
    public function activar(User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);
        $this->comprobarLimiteDeLicencia();

        $empleado->update(['activo' => true]);
        AuditLog::registrar('empleado_activado', $empleado);
        $this->sincronizarFacturacion();

        return back()->with('status', 'Empleado activado.');
    }

    /**
     * Da de baja a un empleado sin borrar sus fichajes: deja de poder entrar y de contar
     * para el precio.
     */
    public function desactivar(User $empleado): RedirectResponse
    {
        $this->autorizarMismaEmpresa($empleado);

        $empleado->update(['activo' => false]);
        AuditLog::registrar('empleado_desactivado', $empleado);
        $this->sincronizarFacturacion();

        return back()->with('status', 'Empleado desactivado.');
    }

    /**
     * Da 404 si el empleado no es de la empresa del admin que hace la petición.
     */
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
            // Tramos del día (turno partido = 2 o 3). Las filas totalmente
            // vacías se ignoran; el resto se valida en tramosNormalizados().
            'tramos' => ['nullable', 'array', 'max:3'],
            'tramos.*.entrada' => ['nullable', 'date_format:H:i'],
            'tramos.*.salida' => ['nullable', 'date_format:H:i'],
            'dias_laborables' => ['nullable', 'array'],
            'dias_laborables.*' => ['integer', 'between:1,7'],
        ];
    }

    /**
     * Quita las filas vacías y comprueba que cada tramo tenga entrada y
     * salida, que la salida sea posterior a la entrada y que los tramos
     * vayan en orden sin solaparse.
     *
     * @return list<array{entrada: string, salida: string}>
     */
    protected function tramosNormalizados(array $data): array
    {
        $tramos = collect($data['tramos'] ?? [])
            ->filter(fn ($t) => ! empty($t['entrada']) || ! empty($t['salida']))
            ->values();

        $anteriorSalida = null;

        foreach ($tramos as $i => $tramo) {
            $campo = 'tramos.'.$i;

            if (empty($tramo['entrada']) || empty($tramo['salida'])) {
                throw ValidationException::withMessages([$campo => 'Cada tramo necesita hora de entrada y de salida.']);
            }

            if ($tramo['salida'] <= $tramo['entrada']) {
                throw ValidationException::withMessages([$campo => 'La salida tiene que ser posterior a la entrada.']);
            }

            if ($anteriorSalida !== null && $tramo['entrada'] < $anteriorSalida) {
                throw ValidationException::withMessages([$campo => 'Los tramos tienen que ir en orden y sin solaparse.']);
            }

            $anteriorSalida = $tramo['salida'];
        }

        return $tramos->map(fn ($t) => ['entrada' => $t['entrada'], 'salida' => $t['salida']])->all();
    }

    /**
     * Convierte los tramos validados del formulario en las columnas del horario del
     * empleado (primera entrada y última salida, tramos y días laborables). Sin horario
     * deja todo vacío.
     */
    protected function datosHorario(array $data): array
    {
        $tramos = $this->tramosNormalizados($data);

        return [
            // hora_entrada/salida_esperada guardan siempre la primera entrada
            // y la última salida del día; el input type="time" manda "H:i" y
            // se normaliza a "H:i:s" para que el formato guardado sea el
            // mismo en cualquier motor de BD (SQLite no normaliza TIME como
            // MySQL).
            'hora_entrada_esperada' => $tramos !== [] ? $tramos[0]['entrada'].':00' : null,
            'hora_salida_esperada' => $tramos !== [] ? end($tramos)['salida'].':00' : null,
            // Solo se guardan los tramos aparte cuando es un turno partido.
            'horario_tramos' => count($tramos) > 1 ? $tramos : null,
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
    /**
     * Con una licencia que tiene tope de empleados, no deja pasar de ese número de
     * empleados activos (da de alta o reactiva solo si queda plaza).
     */
    protected function comprobarLimiteDeLicencia(): void
    {
        $empresa = Auth::user()->empresa;
        $limite = $empresa->limiteEmpleadosPorLicencia();

        if ($limite !== null && $empresa->usuarios()->where('activo', true)->count() >= $limite) {
            throw ValidationException::withMessages([
                'name' => "Tu licencia incluye hasta {$limite} empleados y ya los has alcanzado. Escribe a soporte para ampliarla.",
            ]);
        }
    }

    protected function sincronizarFacturacion(): void
    {
        try {
            Auth::user()->empresa->sincronizarCantidadSuscripcion();
        } catch (ApiErrorException $e) {
            Log::warning('No se pudo sincronizar la cantidad de la suscripción con Stripe: '.$e->getMessage());
        }
    }
}
