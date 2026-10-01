<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Tarifas</h2>
    </x-slot>

    <div class="max-w-lg space-y-6">
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Precios de prueba.</strong> Cámbialos aquí cuando tengas los definitivos —
            se aplican a todas las empresas en cuanto guardes.
        </div>

        <x-card class="p-6">
            <form method="POST" action="{{ route('super-admin.tarifas.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="precio_base_mensual" value="Cuota base mensual (€)" />
                    <x-text-input id="precio_base_mensual" name="precio_base_mensual" type="number" step="0.01" min="0"
                        class="mt-1 block w-full" :value="old('precio_base_mensual', $tarifa->precio_base_mensual)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('precio_base_mensual')" />
                </div>

                <div>
                    <x-input-label for="empleados_incluidos" value="Empleados incluidos en la cuota base" />
                    <x-text-input id="empleados_incluidos" name="empleados_incluidos" type="number" step="1" min="0"
                        class="mt-1 block w-full" :value="old('empleados_incluidos', $tarifa->empleados_incluidos)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('empleados_incluidos')" />
                </div>

                <div>
                    <x-input-label for="precio_empleado_extra" value="Precio por empleado adicional (€/mes)" />
                    <x-text-input id="precio_empleado_extra" name="precio_empleado_extra" type="number" step="0.01" min="0"
                        class="mt-1 block w-full" :value="old('precio_empleado_extra', $tarifa->precio_empleado_extra)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('precio_empleado_extra')" />
                </div>

                <p class="text-xs text-slate-500">
                    Ejemplo: con los valores actuales, una empresa con 12 empleados pagaría
                    {{ number_format($tarifa->calcularPrecioMensual(12), 2) }}€/mes.
                </p>

                @if ($tarifa->actualizadoPor)
                    <p class="text-xs text-slate-400">
                        Última modificación: {{ $tarifa->actualizadoPor->name }}, {{ $tarifa->updated_at->format('d/m/Y H:i') }}.
                    </p>
                @endif

                <div class="flex justify-end">
                    <x-primary-button type="submit">Guardar tarifas</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
