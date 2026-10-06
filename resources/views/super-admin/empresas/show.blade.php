<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">{{ $empresa->nombre }}</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-6 space-y-3 text-sm">
            <p><span class="text-slate-500">NIF:</span> <span class="text-slate-800">{{ $empresa->nif ?? '—' }}</span></p>
            <p><span class="text-slate-500">Email de contacto:</span> <span class="text-slate-800">{{ $empresa->email_contacto }}</span></p>
            <p><span class="text-slate-500">Kiosco:</span> <a href="{{ $empresa->kioskoUrl() }}" target="_blank" class="text-indigo-600 hover:underline break-all">{{ $empresa->kioskoUrl() }}</a></p>
            <p>
                <span class="text-slate-500">Precio estimado:</span>
                <span class="text-slate-800">{{ number_format($tarifa->calcularPrecioMensual($empleadosActivos), 2) }}€/mes</span>
                <span class="text-slate-400">({{ $empleadosActivos }} empleados activos)</span>
            </p>
            <p class="flex items-center gap-2">
                <span class="text-slate-500">Estado:</span>
                <x-badge :tone="$empresa->activa ? 'success' : 'neutral'">
                    {{ $empresa->activa ? 'Activa' : 'Inactiva' }}
                </x-badge>
            </p>

            <div class="pt-2">
                @if ($empresa->activa)
                    <form method="POST" action="{{ route('super-admin.empresas.desactivar', $empresa) }}">
                        @csrf
                        @method('PATCH')
                        <button class="text-xs font-medium text-rose-600 hover:text-rose-500">Desactivar empresa</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('super-admin.empresas.activar', $empresa) }}">
                        @csrf
                        @method('PATCH')
                        <button class="text-xs font-medium text-emerald-600 hover:text-emerald-500">Activar empresa</button>
                    </form>
                @endif
            </div>
        </x-card>

        <x-card class="overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 font-medium text-slate-700 text-sm">Usuarios</div>
            <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Rol</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($empresa->usuarios as $usuario)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700">{{ $usuario->name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $usuario->email }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $usuario->rol }}</td>
                            <td class="px-4 py-3">
                                <x-badge :tone="$usuario->activo ? 'success' : 'neutral'">
                                    {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">Sin usuarios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-card>
    </div>
</x-app-layout>
