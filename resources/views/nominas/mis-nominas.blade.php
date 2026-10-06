<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Mis nóminas</h2>
    </x-slot>

    <div class="space-y-6">
        <p class="text-sm text-slate-500">
            Aquí tienes todas tus nóminas, siempre disponibles. Las sube tu empresa y solo tú puedes verlas
            (y quien las gestiona en la empresa).
        </p>

        <x-card class="overflow-hidden">
            <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nómina</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Subida el</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($nominas as $nomina)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-800 font-medium">{{ $nomina->titulo() }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $nomina->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($nomina->descargada_en)
                                    <x-badge tone="neutral">Descargada</x-badge>
                                @else
                                    <x-badge tone="success">Nueva</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('nominas.descargar', $nomina) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    Descargar PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">Todavía no tienes nóminas subidas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>

        <div>{{ $nominas->links() }}</div>
    </div>
</x-app-layout>
