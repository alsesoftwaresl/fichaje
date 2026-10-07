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

                <div class="border-t border-slate-200 pt-6 space-y-4">
                    <div>
                        <h3 class="font-medium text-slate-900">Plan para asesorías (partners)</h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Lo que paga una asesoría por cada licencia (empresa cliente) que reparte entre sus clientes.
                            Los códigos de licencia nuevos usan estos valores por defecto.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="partner_precio_licencia" value="Precio por licencia (€/mes, lo que paga la asesoría)" />
                        <x-text-input id="partner_precio_licencia" name="partner_precio_licencia" type="number" step="0.01" min="0"
                            class="mt-1 block w-full" :value="old('partner_precio_licencia', $tarifa->partner_precio_licencia)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('partner_precio_licencia')" />
                    </div>

                    <div>
                        <x-input-label for="partner_empleados_incluidos" value="Empleados incluidos en cada licencia" />
                        <x-text-input id="partner_empleados_incluidos" name="partner_empleados_incluidos" type="number" step="1" min="1"
                            class="mt-1 block w-full" :value="old('partner_empleados_incluidos', $tarifa->partner_empleados_incluidos)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('partner_empleados_incluidos')" />
                    </div>

                    <div>
                        <x-input-label for="partner_precio_empleado_extra" value="Precio por empleado adicional en una licencia (€/mes)" />
                        <x-text-input id="partner_precio_empleado_extra" name="partner_precio_empleado_extra" type="number" step="0.01" min="0"
                            class="mt-1 block w-full" :value="old('partner_precio_empleado_extra', $tarifa->partner_precio_empleado_extra)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('partner_precio_empleado_extra')" />
                    </div>

                    <div>
                        <x-input-label for="partner_licencias_minimas" value="Mínimo de licencias por asesoría" />
                        <x-text-input id="partner_licencias_minimas" name="partner_licencias_minimas" type="number" step="1" min="1"
                            class="mt-1 block w-full" :value="old('partner_licencias_minimas', $tarifa->partner_licencias_minimas)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('partner_licencias_minimas')" />
                    </div>

                    <div>
                        <x-input-label for="partner_pvp_recomendado" value="Precio recomendado al cliente final (€/mes, solo informativo)" />
                        <x-text-input id="partner_pvp_recomendado" name="partner_pvp_recomendado" type="number" step="0.01" min="0"
                            class="mt-1 block w-full" :value="old('partner_pvp_recomendado', $tarifa->partner_pvp_recomendado)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('partner_pvp_recomendado')" />
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4 text-xs text-slate-600">
                        <p class="font-medium text-slate-700">Simulación con los valores guardados</p>
                        <p class="mt-1">
                            Margen de la asesoría por licencia:
                            <strong class="{{ $tarifa->partnerMargenPorLicencia() < 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ number_format($tarifa->partnerMargenPorLicencia(), 2, ',', '.') }} €/mes</strong>
                            ({{ number_format((float) $tarifa->partner_pvp_recomendado, 2, ',', '.') }} € al cliente − {{ number_format((float) $tarifa->partner_precio_licencia, 2, ',', '.') }} € a ti).
                        </p>
                        <table class="mt-3 w-full">
                            <thead>
                                <tr class="text-left text-slate-500"><th class="py-1 font-medium">Licencias</th><th class="py-1 font-medium">Paga la asesoría</th><th class="py-1 font-medium">Margen total asesoría</th></tr>
                            </thead>
                            <tbody>
                                @foreach (array_unique([$tarifa->partner_licencias_minimas, 10, 25, 50]) as $n)
                                    <tr class="border-t border-slate-200">
                                        <td class="py-1">{{ $n }}</td>
                                        <td class="py-1">{{ number_format($tarifa->partnerPagoMensual($n), 2, ',', '.') }} €/mes</td>
                                        <td class="py-1">{{ number_format($n * $tarifa->partnerMargenPorLicencia(), 2, ',', '.') }} €/mes</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="text-xs text-slate-500">
                    Ejemplo (plan directo): con los valores actuales, una empresa con 12 empleados pagaría
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
