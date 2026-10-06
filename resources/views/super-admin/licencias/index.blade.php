<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Licencias gratuitas</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-6">
            <h3 class="font-medium text-slate-900 mb-1">Crear código</h3>
            <p class="text-sm text-slate-500 mb-4">
                La empresa lo canjea en Facturación y usa la app sin suscripción mientras la licencia esté vigente.
            </p>
            <form method="POST" action="{{ route('super-admin.licencias.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label for="nota" value="Para quién es (solo lo ves tú)" />
                    <x-text-input id="nota" name="nota" type="text" class="mt-1 block w-full" :value="old('nota')" maxlength="255" placeholder="Ej.: Bar Pepe — piloto" />
                    <x-input-error class="mt-2" :messages="$errors->get('nota')" />
                </div>
                <div>
                    <x-input-label for="meses" value="Duración (meses)" />
                    <x-text-input id="meses" name="meses" type="number" min="1" max="120" class="mt-1 block w-full" :value="old('meses')" placeholder="Vacío = sin caducidad" />
                    <x-input-error class="mt-2" :messages="$errors->get('meses')" />
                </div>
                <div>
                    <x-input-label for="max_usos" value="Veces que se puede canjear" />
                    <x-text-input id="max_usos" name="max_usos" type="number" min="1" max="1000" class="mt-1 block w-full" :value="old('max_usos', 1)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('max_usos')" />
                </div>
                <div>
                    <x-input-label for="canjeable_hasta" value="Canjeable hasta (opcional)" />
                    <x-text-input id="canjeable_hasta" name="canjeable_hasta" type="date" class="mt-1 block w-full" :value="old('canjeable_hasta')" />
                    <x-input-error class="mt-2" :messages="$errors->get('canjeable_hasta')" />
                </div>
                <div class="sm:col-span-2">
                    <x-primary-button>Generar código</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 font-medium text-slate-700 text-sm">Códigos</div>
            <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nota</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Duración</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Usos</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Empresas</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($licencias as $licencia)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono font-medium text-slate-900 select-all">{{ $licencia->codigo }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $licencia->nota ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $licencia->meses ? $licencia->meses.' '.($licencia->meses === 1 ? 'mes' : 'meses') : 'Sin caducidad' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $licencia->usos }} / {{ $licencia->max_usos }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                @forelse ($licencia->empresas as $empresa)
                                    <a href="{{ route('super-admin.empresas.show', $empresa) }}" class="text-indigo-600 hover:underline">{{ $empresa->nombre }}</a>@if (! $loop->last), @endif
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td class="px-4 py-3">
                                @if (! $licencia->activa)
                                    <x-badge tone="neutral">Desactivado</x-badge>
                                @elseif ($licencia->motivoNoCanjeable())
                                    <x-badge tone="neutral">{{ $licencia->usos >= $licencia->max_usos ? 'Agotado' : 'Caducado' }}</x-badge>
                                @else
                                    <x-badge tone="success">Disponible</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('super-admin.licencias.toggle', $licencia) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="text-xs font-medium {{ $licencia->activa ? 'text-rose-600 hover:text-rose-500' : 'text-emerald-600 hover:text-emerald-500' }}">
                                        {{ $licencia->activa ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">Todavía no hay códigos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>
    </div>
</x-app-layout>
