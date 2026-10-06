<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Facturación</h2>
    </x-slot>

    <div class="max-w-lg space-y-6">
        @if (request('suscripcion') === 'ok')
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                Suscripción activada. Gracias.
            </div>
        @elseif (request('suscripcion') === 'cancelada')
            <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                No se completó el pago — puedes intentarlo de nuevo cuando quieras.
            </div>
        @endif

        <x-card class="p-6 space-y-3 text-sm">
            <h3 class="font-medium text-slate-900">Tu plan actual</h3>
            <p><span class="text-slate-500">Cuota base:</span> <span class="text-slate-800">{{ number_format($tarifa->precio_base_mensual, 2) }}€/mes (incluye {{ $tarifa->empleados_incluidos }} empleados)</span></p>
            <p><span class="text-slate-500">Empleados activos:</span> <span class="text-slate-800">{{ $empleadosActivos }}</span></p>
            <p><span class="text-slate-500">Empleados extra:</span> <span class="text-slate-800">{{ $empleadosExtra }} × {{ number_format($tarifa->precio_empleado_extra, 2) }}€</span></p>
            <p class="pt-2 border-t border-slate-100"><span class="text-slate-500">Total estimado:</span> <span class="text-lg font-semibold text-slate-900">{{ number_format($precioMensual, 2) }}€/mes</span></p>
        </x-card>

        <x-card class="p-6">
            @if ($empresa->subscribed('default'))
                <div class="flex items-center gap-2 mb-4">
                    @if ($suscripcion->onGracePeriod())
                        <x-badge tone="indigo">Cancelada — activa hasta el {{ $suscripcion->ends_at->format('d/m/Y') }}</x-badge>
                    @elseif ($suscripcion->onTrial())
                        <x-badge tone="indigo">Prueba gratis — hasta el {{ $suscripcion->trial_ends_at->format('d/m/Y') }}</x-badge>
                    @else
                        <x-badge tone="success">Suscripción activa</x-badge>
                    @endif
                </div>

                @if ($suscripcion->onGracePeriod())
                    <p class="text-sm text-slate-600 mb-4">
                        Has cancelado la suscripción — seguirás teniendo acceso hasta el final del
                        periodo ya pagado ({{ $suscripcion->ends_at->format('d/m/Y') }}). No se te
                        volverá a cobrar después, a menos que te vuelvas a suscribir.
                    </p>
                @elseif ($suscripcion->onTrial())
                    <p class="text-sm text-slate-600 mb-4">
                        Estás en tu periodo de prueba gratis hasta el
                        {{ $suscripcion->trial_ends_at->format('d/m/Y') }}. A partir de entonces se te
                        cobrará {{ number_format($precioMensual, 2) }}€/mes — puedes cancelar antes desde
                        el portal sin que se te cobre nada.
                    </p>
                @else
                    <p class="text-sm text-slate-600 mb-4">
                        Gestiona tu método de pago, descarga facturas o cancela la suscripción desde el
                        portal seguro de Stripe.
                    </p>
                @endif

                <a href="{{ route('admin.facturacion.portal') }}">
                    <x-secondary-button type="button">Gestionar facturación</x-secondary-button>
                </a>
            @else
                <p class="text-sm text-slate-600 mb-4">
                    Todavía no tienes una suscripción activa.
                    {{ $diasPrueba }} días gratis al suscribirte — después se te cobrará
                    {{ number_format($precioMensual, 2) }}€/mes según el número de empleados activos.
                    Puedes cancelar en cualquier momento antes de que acabe la prueba sin que se te
                    cobre nada.
                </p>
                <form method="POST" action="{{ route('admin.facturacion.suscribir') }}">
                    @csrf
                    <x-primary-button type="submit">Empezar prueba gratis de {{ $diasPrueba }} días</x-primary-button>
                </form>
            @endif
        </x-card>

        @if ($facturas->isNotEmpty())
            <x-card class="overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 font-medium text-slate-700 text-sm">Facturas</div>
                <div class="overflow-x-auto"><table class="tabla-apilada min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Importe</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($facturas as $factura)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700">{{ $factura->date()->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $factura->total() }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.facturacion.facturas.descargar', $factura->id) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                        Descargar PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </x-card>
        @endif
    </div>
</x-app-layout>
