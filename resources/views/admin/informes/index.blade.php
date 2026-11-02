{{-- Informes mensuales del registro de jornada (Admin\InformeController). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Informes mensuales</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-4">
            <form method="GET" action="{{ route('admin.informes.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <x-input-label for="mes" value="Mes" />
                    <input id="mes" type="month" name="mes" value="{{ $mes->format('Y-m') }}" max="{{ now()->format('Y-m') }}"
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <x-primary-button type="submit">Ver</x-primary-button>

                <a href="{{ route('admin.informes.pdf', ['mes' => $mes->format('Y-m')]) }}"
                   class="ml-auto text-sm font-medium text-indigo-600 underline hover:text-indigo-500">
                    Descargar el PDF de todos
                </a>
            </form>
        </x-card>

        <x-card class="p-4 text-sm text-slate-600">
            Un resumen por persona con sus horas de cada día y el total del mes, con espacio para firmar.
            Si alguien trabaja a tiempo parcial, la ley pide entregarle cada mes una copia junto con la nómina
            (art. 12.4.c ET). También sirve para enseñarlo a la Inspección o a los representantes.
        </x-card>

        <x-card>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Persona</th>
                            <th class="px-4 py-3 text-right">Horas trabajadas</th>
                            <th class="px-4 py-3 text-right">Previstas</th>
                            <th class="px-4 py-3 text-right">Por encima del horario</th>
                            <th class="px-4 py-3 text-right">Sin salida</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($informes as $informe)
                            <tr>
                                <td class="px-4 py-3 text-slate-900">
                                    {{ $informe['empleado']->name }}
                                    @unless ($informe['empleado']->activo)
                                        <x-badge>De baja</x-badge>
                                    @endunless
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($informe['total'], 2, ',', '.') }} h</td>
                                <td class="px-4 py-3 text-right text-slate-500">
                                    {{ $informe['empleado']->tieneHorario() ? number_format($informe['previstas'], 2, ',', '.').' h' : 'Sin horario' }}
                                </td>
                                <td class="px-4 py-3 text-right {{ $informe['exceso'] > 0 ? 'font-medium text-amber-700' : 'text-slate-500' }}">
                                    {{ number_format($informe['exceso'], 2, ',', '.') }} h
                                </td>
                                <td class="px-4 py-3 text-right {{ $informe['sinSalida'] > 0 ? 'font-medium text-rose-600' : 'text-slate-500' }}">
                                    {{ $informe['sinSalida'] }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.informes.pdf', ['mes' => $mes->format('Y-m'), 'user_id' => $informe['empleado']->id]) }}"
                                       class="text-xs font-medium text-indigo-600 hover:text-indigo-500">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-400">No hay empleados este mes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
