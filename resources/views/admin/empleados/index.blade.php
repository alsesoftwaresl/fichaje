<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-900">Empleados</h2>
            <a href="{{ route('admin.empleados.create') }}">
                <x-primary-button>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Dar de alta empleado
                </x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('pin_generado'))
            @php $pinInfo = session('pin_generado'); @endphp
            <div class="rounded-lg bg-indigo-50 border border-indigo-200 px-4 py-3 text-sm text-indigo-900">
                PIN de kiosco para <strong>{{ $pinInfo['nombre'] }}</strong>:
                <span class="font-mono text-lg font-semibold tracking-widest ml-1">{{ $pinInfo['pin'] }}</span>
                <p class="mt-1 text-indigo-700">Comunícaselo ahora — no podrás volver a verlo, solo regenerarlo.</p>
            </div>
        @endif

        @if (session('acceso_generado'))
            @php $acceso = session('acceso_generado'); @endphp
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">
                Acceso a la web para <strong>{{ $acceso['nombre'] }}</strong>:
                <div class="mt-1 flex flex-wrap items-center gap-x-6 gap-y-1">
                    <span>Usuario (DNI/NIE): <span class="font-mono font-semibold">{{ $acceso['dni'] }}</span></span>
                    <span>Contraseña temporal: <span class="font-mono text-lg font-semibold tracking-wider">{{ $acceso['password'] }}</span></span>
                </div>
                <p class="mt-1 text-emerald-700">
                    Pásasela ahora — no podrás volver a verla. Entrará en la pantalla de inicio de sesión y
                    tendrá que elegir su propia contraseña. Si la pierde, genera otra desde su fila.
                </p>
            </div>
        @endif

        <x-card class="p-4">
            <p class="text-sm font-medium text-slate-700 mb-1">Enlace del kiosco</p>
            <p class="text-xs text-slate-500 mb-2">
                Ábrelo en la tablet u ordenador fijo de la entrada para que los empleados fichen.
            </p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ $empresa->kioskoUrl() }}"
                    class="flex-1 rounded-lg border-slate-300 text-sm bg-slate-50 text-slate-600">
                <a href="{{ $empresa->kioskoUrl() }}" target="_blank">
                    <x-secondary-button type="button">Abrir</x-secondary-button>
                </a>
            </div>

            <form method="POST" action="{{ route('admin.kiosko.modo') }}" class="mt-5 border-t border-slate-100 pt-4">
                @csrf
                @method('PATCH')
                <p class="text-sm font-medium text-slate-700">¿Cómo ficha la gente en el kiosco?</p>
                <div class="mt-2 space-y-2 text-sm">
                    @foreach ([
                        'pin' => ['Con su PIN de 6 números', 'El más seguro: el PIN es personal.'],
                        'dni' => ['Con su DNI o NIE', 'Nadie tiene que recordar nada, pero cualquiera que conozca el DNI de un compañero podría fichar por él.'],
                        'ambos' => ['PIN o DNI, como prefiera cada uno', 'Ficha con el PIN y, si lo ha olvidado, con su DNI. Tiene el mismo riesgo que el DNI.'],
                    ] as $valor => [$titulo, $ayuda])
                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 hover:border-indigo-300">
                            <input type="radio" name="kiosko_modo" value="{{ $valor }}" @checked($empresa->kiosko_modo === $valor) class="mt-1 text-indigo-600 focus:ring-indigo-500">
                            <span><span class="font-medium text-slate-800">{{ $titulo }}</span><span class="block text-xs text-slate-500">{{ $ayuda }}</span></span>
                        </label>
                    @endforeach
                </div>

                <p class="mt-4 text-sm font-medium text-slate-700">¿Pueden fichar también desde su cuenta?</p>
                <input type="hidden" name="fichaje_web" value="0">
                <label class="mt-2 flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                    <input type="checkbox" name="fichaje_web" value="1" @checked($empresa->fichaje_web) class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span><span class="font-medium text-slate-800">Permitir fichar desde el móvil u ordenador</span><span class="block text-xs text-slate-500">Si lo desmarcas, solo se ficha en el kiosco y los empleados solo consultan sus fichajes. Ojo: quien tenga el enlace del kiosco y su PIN o DNI también podría usarlo desde fuera.</span></span>
                </label>

                <x-primary-button class="mt-3">Guardar</x-primary-button>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($empleados as $empleado)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ $empleado->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $empleado->email }}</td>
                            <td class="px-4 py-3">
                                @php $estado = $estados[$empleado->id] ?? null; @endphp
                                @if (! $empleado->activo)
                                    <x-badge tone="neutral">Inactivo</x-badge>
                                @else
                                    <x-badge :tone="match($estado['estado'] ?? null) {
                                        'trabajando' => 'success',
                                        'vacaciones', 'baja' => 'indigo',
                                        'cita' => 'warning',
                                        default => 'neutral',
                                    }">
                                        {{ match($estado['estado'] ?? null) {
                                            'trabajando' => 'Trabajando',
                                            'vacaciones' => 'Vacaciones',
                                            'baja' => $estado['detalle'] ?? 'Baja',
                                            'cita' => 'Salida avisada',
                                            default => 'Fuera',
                                        } }}
                                    </x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('admin.empleados.edit', $empleado) }}" class="text-xs font-medium text-slate-600 hover:text-slate-500">Editar</a>
                                <form method="POST" action="{{ route('admin.empleados.regenerar-pin', $empleado) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                        {{ $empleado->pin_hash ? 'Regenerar PIN' : 'Generar PIN' }}
                                    </button>
                                </form>
                                @if ($empleado->activo && $empleado->dni_nie)
                                    <form method="POST" action="{{ route('admin.empleados.generar-acceso', $empleado) }}" class="inline"
                                          onsubmit="return confirm('Se generará una contraseña temporal nueva y la anterior dejará de valer. ¿Continuar?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Acceso web</button>
                                    </form>
                                @endif
                                @if ($empleado->activo)
                                    <form method="POST" action="{{ route('admin.empleados.desactivar', $empleado) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs font-medium text-rose-600 hover:text-rose-500">Desactivar</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.empleados.activar', $empleado) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs font-medium text-emerald-600 hover:text-emerald-500">Activar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">Todavía no hay empleados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>

        {{ $empleados->links() }}
    </div>
</x-app-layout>
