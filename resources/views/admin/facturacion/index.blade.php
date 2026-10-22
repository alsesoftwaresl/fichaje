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
            <p><span class="text-slate-500">Cuota base:</span> <span class="text-slate-800">{{ number_format($tarifa->precio_base_mensual, 2) }}€/mes, IVA incluido (incluye {{ $tarifa->empleados_incluidos }} empleados)</span></p>
            <p><span class="text-slate-500">Empleados activos:</span> <span class="text-slate-800">{{ $empleadosActivos }}</span></p>
            <p><span class="text-slate-500">Empleados extra:</span> <span class="text-slate-800">{{ $empleadosExtra }} × {{ number_format($tarifa->precio_empleado_extra, 2) }}€</span></p>
            @if ($precioNominas > 0)
                <p><span class="text-slate-500">Módulo Nóminas:</span> <span class="text-slate-800">{{ number_format($precioNominas, 2) }}€/mes</span></p>
            @endif
            <p class="pt-2 border-t border-slate-100"><span class="text-slate-500">Total estimado:</span> <span class="text-lg font-semibold text-slate-900">{{ number_format($precioMensual, 2) }}€/mes</span> <span class="text-slate-500">IVA incluido</span></p>
            @if ((float) $tarifa->iva_porcentaje > 0)
                <p class="text-xs text-slate-500">
                    {{ number_format($tarifa->baseImponible($precioMensual), 2, ',', '.') }} € de base + {{ number_format($tarifa->ivaIncluido($precioMensual), 2, ',', '.') }} € de IVA ({{ $tarifa->ivaTexto() }} %).
                </p>
            @endif
        </x-card>

        @if ($empresa->tieneLicenciaActiva())
            <x-card class="p-6 space-y-2 text-sm">
                <x-badge tone="success">Licencia gratuita activa</x-badge>
                <p class="text-slate-600">
                    {{ $empresa->licencia_hasta ? 'Válida hasta el '.$empresa->licencia_hasta->format('d/m/Y').'.' : 'Sin fecha de caducidad.' }}
                    No se te cobra nada.
                    @if ($empresa->licencia?->max_empleados)
                        Incluye hasta {{ $empresa->licencia->max_empleados }} empleados (ahora tienes {{ $empleadosActivos }}).
                    @endif
                </p>
            </x-card>
        @endif

        {{-- Módulo de Nóminas: solo aparece si se cobra aparte --}}
        @if ($tarifa->nominas_modulo_activo)
            <x-card class="p-6 space-y-3 text-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-medium text-slate-900">Módulo de Nóminas</h3>
                    <span class="font-semibold text-slate-900">{{ number_format((float) $tarifa->nominas_precio_mensual, 2) }}€/mes <span class="font-normal text-slate-500">IVA incluido</span></span>
                </div>
                <p class="text-slate-600">Sube las nóminas de tu equipo y cada empleado las descarga desde su cuenta, con aviso por correo.</p>

                @php
                    $incluidoPorLicencia = $empresa->tieneLicenciaActiva() && $empresa->licencia?->incluye_nominas;
                    $enPrueba = (bool) $empresa->subscription('default')?->onTrial();
                @endphp

                @if ($empresa->modulo_nominas && $empresa->subscribed('default'))
                    <div class="flex flex-wrap items-center gap-3">
                        <x-badge tone="success">Activo</x-badge>
                        <form method="POST" action="{{ route('admin.facturacion.nominas.desactivar') }}" onsubmit="return confirm('¿Quitar el módulo de Nóminas? Las nóminas ya subidas se conservan y tus empleados siguen viéndolas.')">
                            @csrf
                            @method('DELETE')
                            <button class="text-xs font-medium text-rose-600 hover:text-rose-500">Quitar el módulo</button>
                        </form>
                    </div>
                @elseif ($incluidoPorLicencia)
                    <x-badge tone="success">Incluido en tu licencia</x-badge>
                @else
                    @if ($enPrueba)
                        <x-badge tone="indigo">Incluido durante la prueba gratis</x-badge>
                    @endif
                    @if ($empresa->subscribed('default'))
                        <form method="POST" action="{{ route('admin.facturacion.nominas.activar') }}">
                            @csrf
                            <x-primary-button type="submit">Activar el módulo de Nóminas</x-primary-button>
                        </form>
                    @else
                        <p class="text-xs text-slate-500">Se activa desde aquí cuando tengas la suscripción en marcha.</p>
                    @endif
                @endif

                <p class="text-xs text-slate-500">Si no lo tienes activo, tus empleados siguen pudiendo ver y descargar las nóminas que ya están subidas.</p>
            </x-card>
        @endif

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
                        cobrará {{ number_format($precioMensual, 2) }}€/mes (IVA incluido) — puedes cancelar antes desde
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
                    @if ($diasPrueba > 0)
                        {{ $diasPrueba }} días gratis al suscribirte — después se te cobrará
                        {{ number_format($precioMensual, 2) }}€/mes (IVA incluido) según el número de empleados activos.
                        Puedes cancelar en cualquier momento antes de que acabe la prueba sin que se te
                        cobre nada.
                    @else
                        Ya disfrutaste de la prueba gratis: al suscribirte se cobrarán
                        {{ number_format($precioMensual, 2) }}€/mes (IVA incluido) según el número de empleados activos.
                        Puedes cancelar en cualquier momento.
                    @endif
                </p>
                <form method="POST" action="{{ route('admin.facturacion.suscribir') }}">
                    @csrf
                    <x-primary-button type="submit">{{ $diasPrueba > 0 ? 'Empezar prueba gratis de '.$diasPrueba.' días' : 'Suscribirme' }}</x-primary-button>
                </form>

                @unless ($empresa->tieneLicenciaActiva())
                    <form method="POST" action="{{ route('admin.facturacion.licencia') }}" class="mt-6 pt-6 border-t border-slate-100">
                        @csrf
                        <x-input-label for="codigo" value="¿Tienes un código de licencia?" />
                        <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                            <x-text-input id="codigo" name="codigo" type="text" class="block w-full font-mono uppercase" :value="old('codigo')" placeholder="ACH-XXXX-XXXX" maxlength="32" autocomplete="off" required />
                            <x-secondary-button type="submit">Canjear</x-secondary-button>
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('codigo')" />
                    </form>
                @endunless
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
