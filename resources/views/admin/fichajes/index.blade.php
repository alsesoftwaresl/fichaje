<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Fichajes de la empresa</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-4">
            <form method="GET" action="{{ route('admin.fichajes.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <x-input-label value="Empleado" />
                    <select name="user_id" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        @foreach ($empleados as $empleado)
                            <option value="{{ $empleado->id }}" @selected(($filtros['user_id'] ?? null) == $empleado->id)>
                                {{ $empleado->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label value="Desde" />
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <x-input-label value="Hasta" />
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <x-primary-button type="submit">Filtrar</x-primary-button>

                <div class="ml-auto flex items-center gap-1 text-sm font-medium text-indigo-600">
                    <span class="text-slate-400 font-normal mr-1">Exportar:</span>
                    <a href="{{ route('admin.fichajes.exportar', array_merge($filtros, ['formato' => 'csv'])) }}" class="hover:text-indigo-500 underline">CSV</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('admin.fichajes.exportar', array_merge($filtros, ['formato' => 'excel'])) }}" class="hover:text-indigo-500 underline">Excel</a>
                    <span class="text-slate-300">·</span>
                    <a href="{{ route('admin.fichajes.exportar', array_merge($filtros, ['formato' => 'pdf'])) }}" class="hover:text-indigo-500 underline">PDF</a>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Entrada</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Salida</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Horas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Correcciones</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($jornadas as $jornada)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ $jornada['usuario']->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ ($jornada['entrada'] ?? $jornada['salida'])->fecha_hora->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $jornada['entrada']?->fecha_hora->format('H:i:s') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                @if ($jornada['salida'])
                                    {{ $jornada['salida']->fecha_hora->format('H:i:s') }}
                                @else
                                    <x-badge tone="indigo">En curso</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $jornada['horas'] !== null ? number_format($jornada['horas'], 2).' h' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500">
                                @forelse ($jornada['correcciones'] as $correccion)
                                    <div>&rarr; {{ $correccion->fecha_hora_corregida->format('d/m/Y H:i') }} ({{ $correccion->motivo }})</div>
                                @empty
                                    &mdash;
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                @if ($jornada['entrada'])
                                    <button type="button"
                                        x-data
                                        @click="$dispatch('abrir-correccion', { id: {{ $jornada['entrada']->id }}, fecha: '{{ $jornada['entrada']->fecha_hora->format('Y-m-d\TH:i') }}' })"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                        Corregir entrada
                                    </button>
                                @endif
                                @if ($jornada['salida'])
                                    <button type="button"
                                        x-data
                                        @click="$dispatch('abrir-correccion', { id: {{ $jornada['salida']->id }}, fecha: '{{ $jornada['salida']->fecha_hora->format('Y-m-d\TH:i') }}' })"
                                        class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                        Corregir salida
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">No hay fichajes con estos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $jornadas->links() }}
    </div>

    @include('admin.fichajes._correccion-modal')
</x-app-layout>
