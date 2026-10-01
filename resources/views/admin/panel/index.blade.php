<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Panel</h2>
    </x-slot>

    <div class="space-y-6">
        @if ($incidencias->isNotEmpty())
            <x-card class="p-4 border-amber-200 bg-amber-50">
                <h3 class="font-medium text-amber-900 mb-3">Incidencias de hoy</h3>
                <ul class="space-y-1.5 text-sm text-amber-900">
                    @foreach ($incidencias as $incidencia)
                        <li class="flex items-center gap-2">
                            <x-badge :tone="$incidencia['tipo'] === 'retraso' ? 'danger' : 'indigo'">
                                {{ $incidencia['tipo'] === 'retraso' ? 'Retraso' : 'Horas de más' }}
                            </x-badge>
                            <span>{{ $incidencia['empleado']->name }} — {{ $incidencia['detalle'] }}</span>
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
            <table class="min-w-full divide-y divide-slate-200 text-sm">
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
                                    default => 'neutral',
                                }">
                                    {{ match($item['estado']) {
                                        'trabajando' => 'Trabajando',
                                        'vacaciones' => 'Vacaciones',
                                        'baja' => 'Baja',
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
            </table>
        </x-card>
    </div>
</x-app-layout>
