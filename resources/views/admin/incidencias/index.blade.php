<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Control horario</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-4">
            <form method="GET" action="{{ route('admin.incidencias.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <x-input-label value="Empleado" />
                    <select name="user_id" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        @foreach ($empleados as $empleado)
                            <option value="{{ $empleado->id }}" @selected(($filtros['user_id'] ?? null) == $empleado->id)>{{ $empleado->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Desde" />
                    <input type="date" name="desde" value="{{ $filtros['desde'] }}" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <x-input-label value="Hasta" />
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] }}" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <x-primary-button type="submit">Filtrar</x-primary-button>
            </form>
            <p class="mt-3 text-xs text-slate-500">
                Se calcula a partir del horario de cada empleado (se asigna al dar de alta o al editarlo).
                Solo cuentan los días laborables de cada uno; quien tenga vacaciones o baja aprobadas ese
                día no genera incidencias. Máximo {{ $maxDias }} días por consulta.
            </p>
        </x-card>

        <x-card class="overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 font-medium text-slate-700 text-sm">Resumen por empleado</div>
            <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Retrasos</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Min. de retraso</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Sin fichar</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Sin cerrar</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Horas extra</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($resumen as $fila)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $fila['empleado']->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $fila['retrasos'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $fila['minutos_retraso'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $fila['sin_fichar'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $fila['sin_cerrar'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ number_format($fila['horas_extra'], 2) }} h</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">Sin incidencias en este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>

        @if ($incidencias->isNotEmpty())
            <x-card class="overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 font-medium text-slate-700 text-sm">Detalle</div>
                <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Empleado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Incidencia</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($incidencias as $incidencia)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700">{{ $incidencia['fecha']->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $incidencia['empleado']->name }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :tone="\App\Services\IncidenciasCalculador::ETIQUETAS[$incidencia['tipo']][1]">
                                        {{ \App\Services\IncidenciasCalculador::ETIQUETAS[$incidencia['tipo']][0] }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $incidencia['detalle'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </x-card>
        @endif
    </div>
</x-app-layout>
