<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Ausencias</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-4">
            <form method="GET" action="{{ route('admin.ausencias.index') }}" class="flex items-end gap-4">
                <div>
                    <x-input-label value="Estado" />
                    <select name="estado" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Todos</option>
                        <option value="pendiente" @selected($estado === 'pendiente')>Pendiente</option>
                        <option value="aprobada" @selected($estado === 'aprobada')>Aprobada</option>
                        <option value="rechazada" @selected($estado === 'rechazada')>Rechazada</option>
                    </select>
                </div>
                <x-primary-button type="submit">Filtrar</x-primary-button>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Desde</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Hasta</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Motivo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ausencias as $ausencia)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ $ausencia->usuario->name }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ match($ausencia->tipo) { 'vacaciones' => 'Vacaciones', 'baja_medica' => 'Baja médica', default => 'Otro' } }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $ausencia->fecha_inicio->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $ausencia->fecha_fin->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $ausencia->motivo ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :tone="match($ausencia->estado) { 'aprobada' => 'success', 'rechazada' => 'danger', default => 'neutral' }">
                                    {{ ucfirst($ausencia->estado) }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                @if ($ausencia->estado === 'pendiente')
                                    <form method="POST" action="{{ route('admin.ausencias.aprobar', $ausencia) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs font-medium text-emerald-600 hover:text-emerald-500">Aprobar</button>
                                    </form>
                                    <button type="button"
                                        x-data
                                        @click="$dispatch('abrir-rechazo', { id: {{ $ausencia->id }} })"
                                        class="text-xs font-medium text-rose-600 hover:text-rose-500">
                                        Rechazar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">No hay ausencias con este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $ausencias->links() }}
    </div>

    <div
        x-data="{ open: false, ausenciaId: null }"
        @abrir-rechazo.window="open = true; ausenciaId = $event.detail.id"
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
    >
        <div @click.outside="open = false" class="bg-white rounded-xl shadow-lg border border-slate-200 max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Rechazar ausencia</h3>
            <form :action="`/admin/ausencias/${ausenciaId}/rechazar`" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <x-input-label value="Motivo (opcional)" />
                    <textarea name="motivo_rechazo" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <x-secondary-button type="button" @click="open = false">Cancelar</x-secondary-button>
                    <x-danger-button type="submit">Rechazar</x-danger-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
