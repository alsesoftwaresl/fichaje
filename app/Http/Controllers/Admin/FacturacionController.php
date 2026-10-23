<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tarifa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Cashier\Checkout;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Facturación de la empresa con Stripe (vía Laravel Cashier): ver el plan y el precio,
 * suscribirse con prueba gratis, abrir el portal de Stripe y descargar facturas. Queda
 * fuera del middleware de suscripción para que se pueda contratar estando bloqueado.
 */
class FacturacionController extends Controller
{
    /**
     * Días de prueba gratis (con tarjeta) al contratar.
     */
    const DIAS_PRUEBA_GRATIS = 15;

    /**
     * Pantalla de Facturación: plan actual, precio estimado según empleados activos,
     * estado de la suscripción o licencia, y facturas.
     */
    public function index(): View
    {
        $empresa = Auth::user()->empresa;

        // Si canceló el pago, el siguiente intento abre uno nuevo (con los empleados de ahora)
        // en vez de reenviar al que dejó a medias.
        if (request()->query('suscripcion') === 'cancelada') {
            Cache::forget('checkout-abierto:'.$empresa->id);
        }

        // Al volver del Checkout de Stripe puede que el webhook aún no haya
        // llegado: se copia la suscripción desde Stripe para no mostrar
        // "sin suscripción" a quien acaba de contratar.
        if (! $empresa->tieneAcceso()) {
            $empresa->sincronizarSuscripcionesDesdeStripe();
            $empresa->unsetRelation('subscriptions');
        }

        $tarifa = Tarifa::actual();
        $empleadosActivos = $empresa->usuarios()->where('activo', true)->count();

        // Cuota del módulo de Nóminas, solo si se cobra aparte y la empresa lo ha contratado.
        $precioNominas = ($tarifa->nominas_modulo_activo && $empresa->modulo_nominas && $empresa->subscribed('default'))
            ? (float) $tarifa->nominas_precio_mensual
            : 0.0;

        return view('admin.facturacion.index', [
            'precioNominas' => $precioNominas,
            'empresa' => $empresa,
            'tarifa' => $tarifa,
            'empleadosActivos' => $empleadosActivos,
            'precioMensual' => $tarifa->calcularPrecioMensual($empleadosActivos) + $precioNominas,
            'empleadosExtra' => $tarifa->empleadosExtra($empleadosActivos),
            'suscripcion' => $empresa->subscription('default'),
            // 0 si la empresa ya fue cliente: la prueba gratis es solo la primera vez.
            'diasPrueba' => $empresa->puedeUsarPrueba() ? self::DIAS_PRUEBA_GRATIS : 0,
            // Historial de facturas (una por mes mientras la suscripción esté
            // activa). Vacío si la empresa nunca ha llegado a ser cliente en Stripe.
            'facturas' => $empresa->stripe_id ? $empresa->invoices() : collect(),
        ]);
    }

    /**
     * Crea la sesión de Stripe Checkout con la cuota base y los empleados extra, con 15
     * días de prueba solo si la empresa nunca ha sido cliente. Falla con mensaje si las
     * tarifas no se han sincronizado con Stripe.
     *
     * Para no cobrar dos veces: no deja suscribirse a quien ya lo está, y si ya hay un pago
     * abierto (otra pestaña, doble clic, volver atrás) reenvía a ese mismo en vez de abrir
     * otro.
     */
    public function suscribir(): Checkout|RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        $tarifa = Tarifa::actual();

        // Puede haber pagado hace un momento y que el webhook aún no haya llegado.
        if (! $empresa->subscribed('default') && $empresa->stripe_id) {
            $empresa->sincronizarSuscripcionesDesdeStripe();
            $empresa->unsetRelation('subscriptions');
        }

        if ($empresa->subscribed('default')) {
            return redirect()->route('admin.facturacion.index')
                ->with('status', 'Tu empresa ya tiene una suscripción activa.');
        }

        $claveCheckout = 'checkout-abierto:'.$empresa->id;

        if ($url = Cache::get($claveCheckout)) {
            return redirect()->away($url, 303);
        }

        // Sin el tipo de IVA creado en Stripe no se desglosaría el IVA en la factura.
        $ivaPendiente = (float) $tarifa->iva_porcentaje > 0 && ! $tarifa->stripe_tax_rate_id;

        if (! $tarifa->stripe_price_base_id || ! $tarifa->stripe_price_extra_id || $ivaPendiente) {
            return redirect()->route('admin.facturacion.index')
                ->with('status', 'Las tarifas todavía no están sincronizadas con Stripe. Contacta con el soporte.');
        }

        $empleadosActivos = $empresa->usuarios()->where('activo', true)->count();
        $extra = $tarifa->empleadosExtra($empleadosActivos);

        $builder = $empresa->newSubscription('default')
            ->price($tarifa->stripe_price_base_id, 1);

        if ($empresa->puedeUsarPrueba()) {
            $builder->trialDays(self::DIAS_PRUEBA_GRATIS);
        }

        // Una suscripción nueva empieza sin el módulo de Nóminas (su precio no va en este
        // pago). Si se quedara marcado de una suscripción anterior, saldría gratis.
        if ($empresa->modulo_nominas) {
            $empresa->forceFill(['modulo_nominas' => false])->save();
        }

        if ($extra > 0) {
            $builder->price($tarifa->stripe_price_extra_id, $extra);
        }

        try {
            $checkout = $builder->checkout([
                'success_url' => route('admin.facturacion.index').'?suscripcion=ok',
                'cancel_url' => route('admin.facturacion.index').'?suscripcion=cancelada',
                // Datos fiscales de la empresa en la factura (dirección y NIF/CIF).
                'billing_address_collection' => 'required',
                'tax_id_collection' => ['enabled' => true],
                'customer_update' => ['name' => 'auto', 'address' => 'auto'],
            ]);

            Cache::put($claveCheckout, $checkout->url, now()->addMinutes(30));

            return $checkout;
        } catch (ApiErrorException $e) {
            Log::warning('No se pudo crear el pago en Stripe: '.$e->getMessage());

            return redirect()->route('admin.facturacion.index')
                ->with('status', 'No se pudo iniciar el pago. Inténtalo de nuevo en unos minutos o escríbenos.');
        }
    }

    /**
     * Redirige al portal de Stripe para cambiar la tarjeta o cancelar.
     */
    public function portal(): RedirectResponse
    {
        return Auth::user()->empresa->redirectToBillingPortal(route('admin.facturacion.index'));
    }

    /**
     * Descarga en PDF una factura de la empresa (falla si no es suya).
     */
    public function descargarFactura(string $factura): Response
    {
        $empresa = Auth::user()->empresa;
        $nombreArchivo = 'factura-'.$empresa->findInvoiceOrFail($factura)->date()->format('Y-m');

        return $empresa->downloadInvoice($factura, [
            'vendor' => 'Achrono',
            'product' => 'Suscripcion',
        ], $nombreArchivo);
    }
}
