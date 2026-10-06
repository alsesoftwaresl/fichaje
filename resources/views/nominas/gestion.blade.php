<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Nóminas de la empresa</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-6">
            <h3 class="font-medium text-slate-900">Subir una nómina</h3>
            <p class="mt-1 mb-4 text-sm text-slate-500">
                Elige al empleado, el mes al que corresponde y el PDF (máx. 5 MB). El empleado la verá al momento
                en "Mis nóminas" y podrá descargarla cuando quiera.
            </p>

            <form method="POST" action="{{ route('nominas.gestion.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="grid sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label value="Empleado" />
                        <select name="user_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Elige un empleado…</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" @selected(old('user_id') == $empleado->id)>{{ $empleado->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
                    </div>
                    <div>
                        <x-input-label value="Mes" />
                        <x-text-input name="mes" type="month" class="mt-1 block w-full" :value="old('mes', now()->subMonth()->format('Y-m'))" required />
                        <x-input-error class="mt-2" :messages="$errors->get('mes')" />
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label value="Descripción (opcional)" />
                        <x-text-input name="descripcion" type="text" class="mt-1 block w-full" :value="old('descripcion')" maxlength="120" placeholder="Ej.: Paga extra de Navidad" />
                        <x-input-error class="mt-2" :messages="$errors->get('descripcion')" />
                    </div>
                    <div>
                        <x-input-label value="Archivo PDF" />
                        <input type="file" name="archivo" accept="application/pdf,.pdf" required
                               class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error class="mt-2" :messages="$errors->get('archivo')" />
                    </div>
                </div>

                <x-primary-button type="submit">Subir nómina</x-primary-button>
            </form>
        </x-card>

        <x-card class="p-4">
            <form method="GET" action="{{ route('nominas.gestion.index') }}" class="flex flex-wrap items-end gap-4">
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
                    <x-input-label value="Mes" />
                    <input type="month" name="mes" value="{{ $filtros['mes'] ?? '' }}" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <x-primary-button type="submit">Filtrar</x-primary-button>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Empleado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nómina</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Subida</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Vista por el empleado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($nominas as $nomina)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-800 font-medium">{{ $nomina->empleado->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $nomina->titulo() }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $nomina->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($nomina->descargada_en)
                                    <x-badge tone="success">{{ $nomina->descargada_en->format('d/m/Y') }}</x-badge>
                                @else
                                    <x-badge tone="neutral">Sin abrir</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('nominas.descargar', $nomina) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Descargar</a>
                                <form method="POST" action="{{ route('nominas.gestion.destroy', $nomina) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar esta nómina? El empleado dejará de verla y no se puede deshacer.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-medium text-rose-600 hover:text-rose-500">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">No hay nóminas con estos filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>

        <div>{{ $nominas->links() }}</div>
    </div>
</x-app-layout>
