<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Cashier\Cashier;

/**
 * Tarifa global de la plataforma (una sola fila): cuota base, empleados incluidos y precio
 * por empleado extra, más los ids de producto y precios de Stripe. La edita el super
 * admin.
 */
class Tarifa extends Model
{
    /**
     * Campos que se pueden rellenar.
     */
    protected $fillable = [
        'precio_base_mensual',
        'empleados_incluidos',
        'precio_empleado_extra',
        'partner_precio_licencia',
        'partner_empleados_incluidos',
        'partner_precio_empleado_extra',
        'partner_licencias_minimas',
        'partner_pvp_recomendado',
        'iva_porcentaje',
        'stripe_tax_rate_id',
        'stripe_modo',
        'actualizado_por',
        'stripe_product_id',
        'stripe_price_base_id',
        'stripe_price_extra_id',
    ];

    /**
     * Importes con 2 decimales y número entero de empleados.
     */
    protected function casts(): array
    {
        return [
            'precio_base_mensual' => 'decimal:2',
            'precio_empleado_extra' => 'decimal:2',
            'empleados_incluidos' => 'integer',
            'partner_precio_licencia' => 'decimal:2',
            'partner_empleados_incluidos' => 'integer',
            'partner_precio_empleado_extra' => 'decimal:2',
            'partner_licencias_minimas' => 'integer',
            'partner_pvp_recomendado' => 'decimal:2',
            'iva_porcentaje' => 'decimal:2',
        ];
    }

    /**
     * Única fila de configuración global. Si por lo que sea no existe
     * (entorno recién instalado sin seed), se crea con los mismos valores
     * de prueba que trae la migración.
     */
    public static function actual(): self
    {
        return static::first() ?? static::create([
            'precio_base_mensual' => 29.00,
            'empleados_incluidos' => 5,
            'precio_empleado_extra' => 1.00,
        ]);
    }

    /**
     * Precio mensual para un número de empleados activos: cuota base + extras × precio
     * por extra.
     */
    public function calcularPrecioMensual(int $empleadosActivos): float
    {
        $extra = $this->empleadosExtra($empleadosActivos);

        return round((float) $this->precio_base_mensual + $extra * (float) $this->precio_empleado_extra, 2);
    }

    /**
     * Cuántos empleados superan los incluidos en la cuota base.
     */
    /**
     * Cuota base lista para mostrar: "29" si no tiene céntimos, "28,99" si los tiene
     * (así la web nunca redondea un precio que luego no coincide con el cobro).
     */
    public function precioBaseTexto(): string
    {
        $texto = number_format((float) $this->precio_base_mensual, 2, ',', '.');

        return str_ends_with($texto, ',00') ? substr($texto, 0, -3) : $texto;
    }

    /** Lo que paga una asesoría al mes por un número de licencias. */
    public function partnerPagoMensual(int $licencias): float
    {
        return round($licencias * (float) $this->partner_precio_licencia, 2);
    }

    /** Lo que gana la asesoría por licencia si vende al precio recomendado (puede ser negativo). */
    public function partnerMargenPorLicencia(): float
    {
        return round((float) $this->partner_pvp_recomendado - (float) $this->partner_precio_licencia, 2);
    }

    /** Porcentaje de IVA sin ceros de sobra: "21" o "10,5". */
    public function ivaTexto(): string
    {
        return rtrim(rtrim(number_format((float) $this->iva_porcentaje, 2, ',', ''), '0'), ',');
    }

    /** Base imponible de un importe que ya lleva el IVA incluido. */
    public function baseImponible(float $conIva): float
    {
        return round($conIva / (1 + (float) $this->iva_porcentaje / 100), 2);
    }

    /** Parte de un importe con IVA incluido que corresponde al IVA. */
    public function ivaIncluido(float $conIva): float
    {
        return round($conIva - $this->baseImponible($conIva), 2);
    }

    public function empleadosExtra(int $empleadosActivos): int
    {
        return max(0, $empleadosActivos - $this->empleados_incluidos);
    }

    /**
     * Super admin que editó la tarifa por última vez.
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Crea (o renueva) en Stripe el Product y los dos Price que representan
     * estas tarifas. Los Price de Stripe son inmutables en el importe, así
     * que cada cambio de precio archiva los anteriores y crea unos nuevos —
     * las suscripciones ya activas con el precio antiguo no se tocan solas,
     * hay que migrarlas aparte si se quiere aplicar el precio nuevo.
     */
    public function sincronizarConStripe(): void
    {
        if (app()->environment('testing')) {
            // Evita llamadas reales a la API de Stripe en cada ejecución de tests.
            return;
        }

        // Al pasar de claves de prueba a claves reales, todo lo creado en Stripe (productos,
        // precios, IVA, clientes y suscripciones) deja de existir: se olvida y se vuelve a crear.
        $modoAnterior = $this->stripe_modo ?? ($this->stripe_product_id ? 'test' : null);

        if ($modoAnterior !== null && $modoAnterior !== static::modoStripe()) {
            $this->olvidarDatosDeStripe();
        }

        $this->stripe_modo = static::modoStripe();

        $stripe = Cashier::stripe();

        if (! $this->stripe_product_id) {
            $this->stripe_product_id = $stripe->products->create([
                'name' => 'Achrono — Suscripción',
            ])->id;
        }

        foreach (array_filter([$this->stripe_price_base_id, $this->stripe_price_extra_id]) as $precioAntiguo) {
            $stripe->prices->update($precioAntiguo, ['active' => false]);
        }

        $this->stripe_price_base_id = $stripe->prices->create([
            'product' => $this->stripe_product_id,
            'unit_amount' => (int) round(((float) $this->precio_base_mensual) * 100),
            'currency' => 'eur',
            'recurring' => ['interval' => 'month'],
            'nickname' => 'Cuota base',
        ])->id;

        $this->stripe_price_extra_id = $stripe->prices->create([
            'product' => $this->stripe_product_id,
            'unit_amount' => (int) round(((float) $this->precio_empleado_extra) * 100),
            'currency' => 'eur',
            'recurring' => ['interval' => 'month'],
            'nickname' => 'Empleado extra',
        ])->id;

        $this->sincronizarIvaConStripe($stripe);

        $this->save();
    }

    /** "live" si las claves de Stripe son las reales (sk_live_...), "test" en cualquier otro caso. */
    public static function modoStripe(): string
    {
        return str_starts_with((string) config('cashier.secret'), 'sk_live_') ? 'live' : 'test';
    }

    /**
     * Borra de la base de datos todo lo que apunta a objetos de Stripe del otro modo:
     * ids de producto, precios e IVA de la tarifa, y clientes y suscripciones de las
     * empresas. Solo se ejecuta al cambiar de modo, cuando todo eso era de prueba.
     */
    public function olvidarDatosDeStripe(): void
    {
        \Illuminate\Support\Facades\Log::warning('Cambio de modo de Stripe: se olvidan los datos del modo anterior.');

        $this->forceFill([
            'stripe_product_id' => null,
            'stripe_price_base_id' => null,
            'stripe_price_extra_id' => null,
            'stripe_tax_rate_id' => null,
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () {
            \Illuminate\Support\Facades\DB::table('subscription_items')->delete();
            \Illuminate\Support\Facades\DB::table('subscriptions')->delete();
            \Illuminate\Support\Facades\DB::table('empresas')->update([
                'stripe_id' => null,
                'pm_type' => null,
                'pm_last_four' => null,
                'trial_ends_at' => null,
            ]);
        });
    }

    /**
     * Crea en Stripe el tipo de IVA (incluido en el precio) que se aplica a las
     * suscripciones. Si el porcentaje cambia, archiva el anterior y crea uno nuevo; las
     * suscripciones ya hechas conservan el suyo.
     */
    protected function sincronizarIvaConStripe($stripe): void
    {
        $porcentaje = (float) $this->iva_porcentaje;

        if ($porcentaje <= 0) {
            $this->stripe_tax_rate_id = null;

            return;
        }

        if ($this->stripe_tax_rate_id) {
            $actual = $stripe->taxRates->retrieve($this->stripe_tax_rate_id);

            if ($actual->active && $actual->inclusive && abs((float) $actual->percentage - $porcentaje) < 0.001) {
                return;
            }

            $stripe->taxRates->update($this->stripe_tax_rate_id, ['active' => false]);
        }

        $this->stripe_tax_rate_id = $stripe->taxRates->create([
            'display_name' => 'IVA',
            'description' => 'IVA incluido en el precio',
            'percentage' => $porcentaje,
            'inclusive' => true,
            'country' => 'ES',
            'jurisdiction' => 'ES',
            'tax_type' => 'vat',
        ])->id;
    }
}
