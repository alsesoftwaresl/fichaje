<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Mis ausencias</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-6">
            <h3 class="font-medium text-slate-900 mb-4">Solicitar vacaciones o baja</h3>
            <form method="POST" action="{{ route('ausencias.store') }}" class="space-y-4">
                @csrf

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label value="Tipo" />
                        <select name="tipo" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="vacaciones">Vacaciones</option>
                            <option value="baja_medica">Baja médica</option>
                            <option value="otro">Otro</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('tipo')" />
                    </div>
                    <div>
                        <x-input-label value="Desde" />
                        <x-text-input name="fecha_inicio" type="date" class="mt-1 block w-full" :value="old('fecha_inicio')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('fecha_inicio')" />
                    </div>
                    <div>
                        <x-input-label value="Hasta" />
                        <x-text-input name="fecha_fin" type="date" class="mt-1 block w-full" :value="old('fecha_fin')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('fecha_fin')" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Motivo (opcional)" />
                    <textarea name="motivo" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('motivo') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('motivo')" />
                </div>

                <x-primary-button type="submit">Enviar solicitud</x-primary-button>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Desde</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Hasta</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Motivo rechazo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ausencias as $ausencia)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">
                                {{ match($ausencia->tipo) { 'vacaciones' => 'Vacaciones', 'baja_medica' => 'Baja médica', default => 'Otro' } }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $ausencia->fecha_inicio->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $ausencia->fecha_fin->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <x-badge :tone="match($ausencia->estado) { 'aprobada' => 'success', 'rechazada' => 'danger', default => 'neutral' }">
                                    {{ ucfirst($ausencia->estado) }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $ausencia->motivo_rechazo ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">Todavía no has solicitado ninguna ausencia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $ausencias->links() }}
    </div>
</x-app-layout>
