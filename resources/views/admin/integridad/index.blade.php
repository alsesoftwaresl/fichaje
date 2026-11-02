{{-- Resultado de comprobar la huella de integridad del registro (IntegridadController). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Integridad del registro de jornada</h2>
    </x-slot>

    <div class="space-y-6">
        <x-card class="p-6">
            @if ($resultado['problemas'] === [])
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div><x-badge tone="success">Correcto</x-badge></div>
                    <p class="text-sm text-slate-700">
                        Se han comprobado {{ $resultado['fichajes'] }} fichajes y {{ $resultado['correcciones'] }} correcciones
                        de {{ $empresa->nombre }}: ninguno se ha modificado ni borrado fuera de la aplicación.
                    </p>
                </div>
            @else
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div><x-badge tone="danger">Revisar</x-badge></div>
                    <p class="text-sm text-slate-700">
                        De {{ $resultado['fichajes'] }} fichajes y {{ $resultado['correcciones'] }} correcciones,
                        {{ count($resultado['problemas']) }} no cuadran con su sello. Eso indica un cambio hecho
                        directamente en la base de datos. Escríbenos a soporte para revisarlo.
                    </p>
                </div>

                <ul class="mt-4 divide-y divide-slate-100 text-sm">
                    @foreach ($resultado['problemas'] as $p)
                        <li class="py-2 text-slate-700">
                            <span class="font-medium">{{ ucfirst($p['tipo']) }} #{{ $p['id'] }}</span> ({{ $p['fecha'] }}): {{ $p['detalle'] }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card class="p-6 text-sm text-slate-600 space-y-2">
            <h3 class="font-medium text-slate-900">¿Cómo funciona?</h3>
            <p>
                Cada fichaje y cada corrección se guardan con un sello digital calculado a partir de sus
                datos y del registro anterior. Nadie puede cambiar ni borrar un fichaje sin que el sello
                deje de cuadrar, ni siquiera con acceso directo a la base de datos.
            </p>
            <p>
                Las correcciones de hora y las salidas que añade la empresa no rompen el sello: se guardan
                como registros nuevos, con su motivo y su autor, y el fichaje original se conserva.
            </p>
        </x-card>
    </div>
</x-app-layout>
