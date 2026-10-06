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

        $this->save();
    }
}
