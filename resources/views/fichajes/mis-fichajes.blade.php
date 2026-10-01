<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Mis fichajes</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-8 text-center">
            <p class="text-sm text-slate-500 mb-4">Próximo fichaje</p>
            <form method="POST" action="{{ route('fichajes.store') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl px-8 py-4 text-lg font-semibold text-white shadow-sm transition
                        {{ $siguienteTipo === 'entrada' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-rose-600 hover:bg-rose-500' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                        <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ $siguienteTipo === 'entrada' ? 'Fichar entrada' : 'Fichar salida' }}
                </button>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Entrada</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Salida</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Horas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Correcciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($jornadas as $jornada)
                        <tr class="hover:bg-slate-50">
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
                                    <div>Corregido a {{ $correccion->fecha_hora_corregida->format('d/m/Y H:i') }} — {{ $correccion->motivo }}</div>
                                @empty
                                    &mdash;
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">Todavía no hay fichajes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $jornadas->links() }}
    </div>
</x-app-layout>
