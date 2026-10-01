<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-900">Empresas</h2>
            <a href="{{ route('super-admin.empresas.create') }}">
                <x-primary-button>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Dar de alta empresa
                </x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <x-card class="overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Usuarios</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Precio estimado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($empresas as $empresa)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('super-admin.empresas.show', $empresa) }}" class="text-indigo-600 hover:text-indigo-500 font-medium">
                                    {{ $empresa->nombre }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $empresa->usuarios_count }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ number_format($tarifa->calcularPrecioMensual($empresa->usuarios_activos_count), 2) }}€/mes</td>
                            <td class="px-4 py-3">
                                <x-badge :tone="$empresa->activa ? 'success' : 'neutral'">
                                    {{ $empresa->activa ? 'Activa' : 'Inactiva' }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($empresa->activa)
                                    <form method="POST" action="{{ route('super-admin.empresas.desactivar', $empresa) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs font-medium text-rose-600 hover:text-rose-500">Desactivar</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('super-admin.empresas.activar', $empresa) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-xs font-medium text-emerald-600 hover:text-emerald-500">Activar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">Todavía no hay empresas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        {{ $empresas->links() }}
    </div>
</x-app-layout>
