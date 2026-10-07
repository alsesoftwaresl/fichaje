<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tarifa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
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

        // Al volver del Checkout de Stripe puede que el webhook aún no haya
        // llegado: se copia la suscripción desde Stripe para no mostrar
        // "sin suscripción" a quien acaba de contratar.
        if (! $empresa->tieneAcceso()) {
            $empresa->sincronizarSuscripcionesDesdeStripe();
            $empresa->unsetRelation('subscriptions');
        }

        $tarifa = Tarifa::actual();
        $empleadosActivos = $empresa->usuarios()->where('activo', true)->count();

        return view('admin.facturacion.index', [
            'empresa' => $empresa,
            'tarifa' => $tarifa,
            'empleadosActivos' => $empleadosActivos,
            'precioMensual' => $tarifa->calcularPrecioMensual($empleadosActivos),
            'empleadosExtra' => $tarifa->empleadosExtra($empleadosActivos),
            'suscripcion' => $empresa->subscription('default'),
            'diasPrueba' => self::DIAS_PRUEBA_GRATIS,
            // Historial de facturas (una por mes mientras la suscripción esté
            // activa). Vacío si la empresa nunca ha llegado a ser cliente en Stripe.
            'facturas' => $empresa->stripe_id ? $empresa->invoices() : collect(),
        ]);
    }

    /**
     * Crea la sesión de Stripe Checkout con la cuota base y los empleados extra, con 15
     * días de prueba. Falla con mensaje si las tarifas no se han sincronizado con Stripe.
     */
    public function suscribir(): Checkout|RedirectResponse
    {
        $empresa = Auth::user()->empresa;
        $tarifa = Tarifa::actual();

        // Sin el tipo de IVA creado en Stripe no se desglosaría el IVA en la factura.
        $ivaPendiente = (float) $tarifa->iva_porcentaje > 0 && ! $tarifa->stripe_tax_rate_id;

        if (! $tarifa->stripe_price_base_id || ! $tarifa->stripe_price_extra_id || $ivaPendiente) {
            return redirect()->route('admin.facturacion.index')
                ->with('status', 'Las tarifas todavía no están sincronizadas con Stripe. Contacta con el soporte.');
        }

        $empleadosActivos = $empresa->usuarios()->where('activo', true)->count();
        $extra = $tarifa->empleadosExtra($empleadosActivos);

        $builder = $empresa->newSubscription('default')
            ->price($tarifa->stripe_price_base_id, 1)
            ->trialDays(self::DIAS_PRUEBA_GRATIS);

        if ($extra > 0) {
            $builder->price($tarifa->stripe_price_extra_id, $extra);
        }

        try {
            return $builder->checkout([
                'success_url' => route('admin.facturacion.index').'?suscripcion=ok',
                'cancel_url' => route('admin.facturacion.index').'?suscripcion=cancelada',
                // Datos fiscales de la empresa en la factura (dirección y NIF/CIF).
                'billing_address_collection' => 'required',
                'tax_id_collection' => ['enabled' => true],
                'customer_update' => ['name' => 'auto', 'address' => 'auto'],
            ]);
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
