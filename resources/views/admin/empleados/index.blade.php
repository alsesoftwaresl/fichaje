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

        <x-card class="p-4">
            <p class="text-sm font-medium text-slate-700 mb-1">Enlace del kiosco</p>
            <p class="text-xs text-slate-500 mb-2">
                Ábrelo en la tablet u ordenador fijo de la entrada para que los empleados fichen con su PIN.
            </p>
            <div class="flex items-center gap-2">
                <input type="text" readonly value="{{ $empresa->kioskoUrl() }}"
                    class="flex-1 rounded-lg border-slate-300 text-sm bg-slate-50 text-slate-600">
                <a href="{{ $empresa->kioskoUrl() }}" target="_blank">
                    <x-secondary-button type="button">Abrir</x-secondary-button>
                </a>
            </div>
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
