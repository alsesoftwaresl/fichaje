<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Panel</h2>
    </x-slot>

    <div class="space-y-6">
        {{-- Pregunta de una sola vez: cómo se ficha en el kiosco (por defecto, con PIN). --}}
        @unless (Auth::user()->empresa->kiosko_modo_elegido)
            <x-card class="border-indigo-200 bg-indigo-50 p-5">
                <h3 class="font-semibold text-indigo-950">¿Cómo quieres que fichen tus empleados en el kiosco?</h3>
                <p class="mt-1 text-sm text-indigo-900/80">Puedes cambiarlo cuando quieras en Empleados. Empezamos con PIN, que es lo más seguro.</p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <form method="POST" action="{{ route('admin.kiosko.modo') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="kiosko_modo" value="pin">
                        <button class="w-full rounded-lg border border-indigo-300 bg-white p-4 text-left transition hover:border-indigo-500">
                            <span class="block font-semibold text-slate-900">Con un PIN de 6 números <span class="ml-1 rounded-full bg-accent-100 px-2 py-0.5 text-xs font-medium text-accent-800">Recomendado</span></span>
                            <span class="mt-1 block text-sm text-slate-600">Cada empleado tiene su PIN personal. Es el más seguro.</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.kiosko.modo') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="kiosko_modo" value="dni">
                        <button class="w-full rounded-lg border border-indigo-300 bg-white p-4 text-left transition hover:border-indigo-500">
                            <span class="block font-semibold text-slate-900">Con su DNI o NIE</span>
                            <span class="mt-1 block text-sm text-slate-600">Nadie tiene que recordar nada, pero cualquiera que conozca el DNI de un compañero podría fichar por él.</span>
                        </button>
                    </form>
                </div>
                <p class="mt-3 text-xs text-indigo-900/70">También puedes permitir las dos formas desde <a href="{{ route('admin.empleados.index') }}" class="font-medium underline">Empleados</a>.</p>
            </x-card>
        @endunless

        @if ($incidencias->isNotEmpty())
            <x-card class="p-4 border-amber-200 bg-amber-50">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="font-medium text-amber-900">Incidencias de hoy</h3>
                    <a href="{{ route('admin.incidencias.index') }}" class="text-sm font-medium text-amber-900 underline">Ver control horario</a>
                </div>
                <ul class="space-y-1.5 text-sm text-amber-900">
                    @foreach ($incidencias as $incidencia)
                        <li class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <x-badge :tone="\App\Services\IncidenciasCalculador::ETIQUETAS[$incidencia['tipo']][1]">
                                {{ \App\Services\IncidenciasCalculador::ETIQUETAS[$incidencia['tipo']][0] }}
                            </x-badge>
                            <span>{{ $incidencia['empleado']->name }} — {{ $incidencia['detalle'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if ($citasHoy->isNotEmpty())
            <x-card class="p-4">
                <h3 class="font-medium text-slate-900 mb-3">Salidas avisadas para hoy</h3>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($citasHoy as $cita)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <span class="text-slate-700">
                                <strong>{{ $cita->usuario->name }}</strong>
                                · {{ $cita->rango() }}
                                <span class="text-slate-500">— {{ $cita->motivo }}</span>
                            </span>
                            <form method="POST" action="{{ route('citas.anular', $cita) }}">
                                @csrf
                                @method('PATCH')
                                <button class="text-xs font-medium text-rose-600 hover:text-rose-500">Anular</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif

        @if ($ausenciasPendientes > 0)
            <x-card class="p-4 border-indigo-200 bg-indigo-50">
                <p class="text-sm text-indigo-900">
                    Tienes <strong>{{ $ausenciasPendientes }}</strong>
                    {{ $ausenciasPendientes === 1 ? 'solicitud de ausencia pendiente' : 'solicitudes de ausencia pendientes' }}
                    de revisar.
                    <a href="{{ route('admin.ausencias.index', ['estado' => 'pendiente']) }}" class="font-medium underline">Ver</a>
                </p>
            </x-card>
        @endif

        <x-card class="overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 font-medium text-slate-700 text-sm">Equipo ahora mismo</div>
            <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($estadoEquipo as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ $item['empleado']->name }}</td>
                            <td class="px-4 py-3">
                                <x-badge :tone="match($item['estado']) {
                                    'trabajando' => 'success',
                                    'vacaciones', 'baja' => 'indigo',
                                    'cita' => 'warning',
                                    default => 'neutral',
                                }">
                                    {{ match($item['estado']) {
                                        'trabajando' => 'Trabajando',
                                        'vacaciones' => 'Vacaciones',
                                        'baja' => 'Baja',
                                        'cita' => 'Salida avisada',
                                        default => 'Fuera',
                                    } }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $item['detalle'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-400">Todavía no hay empleados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>
    </div>
</x-app-layout>
