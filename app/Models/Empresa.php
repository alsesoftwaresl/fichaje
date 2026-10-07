<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Stripe\Exception\ApiErrorException;

/**
 * Empresa cliente (tenant). Es también el "cliente" de Stripe (Cashier) al que cuelgan
 * las suscripciones. Puede usar la app con una suscripción activa o con una licencia
 * gratuita vigente.
 */
class Empresa extends Model
{
    use Billable, HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'nif',
        'email_contacto',
        'activa',
    ];

    /**
     * La marca de activa es booleana y la fecha de fin de licencia es Carbon.
     */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'licencia_hasta' => 'date',
        ];
    }

    /**
     * Al crear una empresa genera el token secreto de su URL de kiosco.
     */
    protected static function booted(): void
    {
        static::creating(function (self $empresa) {
            $empresa->kiosko_token ??= Str::random(32);
        });
    }

    /**
     * Usuarios (admin y empleados) de la empresa.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Tipos de IVA que Cashier aplica a las suscripciones de esta empresa (el IVA va
     * incluido en el precio; ver Tarifa::sincronizarIvaConStripe).
     */
    public function taxRates(): array
    {
        $id = Tarifa::actual()->stripe_tax_rate_id;

        return $id ? [$id] : [];
    }

    /**
     * Licencia gratuita canjeada, si la hay.
     */
    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class);
    }

    /** Licencia gratuita canjeada y todavía vigente (sin fecha = sin caducidad). */
    public function tieneLicenciaActiva(): bool
    {
        return $this->licencia_id !== null
            && ($this->licencia_hasta === null || ! $this->licencia_hasta->endOfDay()->isPast());
    }

    /**
     * Tope de empleados activos si la empresa usa la app con una licencia (y no con una
     * suscripción propia). null = sin tope.
     */
    public function limiteEmpleadosPorLicencia(): ?int
    {
        if (! $this->tieneLicenciaActiva() || $this->subscribed('default')) {
            return null;
        }

        return $this->licencia?->max_empleados;
    }

    /** Puede usar la app: licencia gratuita vigente o suscripción activa. */
    public function tieneAcceso(): bool
    {
        return $this->tieneLicenciaActiva() || $this->subscribed('default');
    }

    /**
     * URL pública del kiosco de fichaje de esta empresa.
     */
    public function kioskoUrl(): string
    {
        return route('kiosko.show', $this->kiosko_token);
    }

    // Cashier usa name/email por defecto; esta empresa los llama nombre/email_contacto.
    public function stripeName(): ?string
    {
        return $this->nombre;
    }

    /**
     * Email con el que se da de alta como cliente en Stripe.
     */
    public function stripeEmail(): ?string
    {
        return $this->email_contacto;
    }

    /**
     * Copia a la base de datos local las suscripciones que Stripe tiene para
     * esta empresa, igual que haría el webhook de Cashier. Hace falta porque
     * al volver del pago el usuario llega antes que el webhook (o el webhook
     * no llega nunca, p. ej. en local sin "stripe listen"), y sin esta copia
     * la app lo trataría como no suscrito y le bloquearía todo.
     *
     * Si Stripe no responde no pasa nada: se queda como estaba y el webhook,
     * cuando llegue, lo arregla.
     */
    public function sincronizarSuscripcionesDesdeStripe(): void
    {
        if (! $this->stripe_id) {
            return;
        }

        try {
            $suscripciones = $this->stripe()->subscriptions->all([
                'customer' => $this->stripe_id,
                'status' => 'all',
                'limit' => 10,
            ]);
        } catch (ApiErrorException $e) {
            Log::warning('No se pudo sincronizar la suscripción desde Stripe: '.$e->getMessage());

            return;
        }

        foreach ($suscripciones->data as $remota) {
            $items = $remota->items->data;
            $unSoloPrecio = count($items) === 1;

            $finaliza = match (true) {
                (bool) $remota->ended_at => $remota->ended_at,
                (bool) $remota->cancel_at => $remota->cancel_at,
                default => null,
            };

            $local = $this->subscriptions()->updateOrCreate(
                ['stripe_id' => $remota->id],
                [
                    'type' => 'default',
                    'stripe_status' => $remota->status,
                    'stripe_price' => $unSoloPrecio ? $items[0]->price->id : null,
                    'quantity' => $unSoloPrecio ? ($items[0]->quantity ?? null) : null,
                    'trial_ends_at' => $remota->trial_end ? Carbon::createFromTimestamp($remota->trial_end) : null,
                    'ends_at' => $finaliza ? Carbon::createFromTimestamp($finaliza) : null,
                ]
            );

            foreach ($items as $item) {
                $local->items()->updateOrCreate(
                    ['stripe_id' => $item->id],
                    [
                        'stripe_product' => $item->price->product,
                        'stripe_price' => $item->price->id,
                        'quantity' => $item->quantity ?? null,
                    ]
                );
            }
        }
    }

    /**
     * Ajusta en Stripe la cantidad del precio "empleado extra" según el
     * número de empleados activos. No hace nada si la empresa no tiene
     * suscripción activa todavía (aún no se ha dado de alta en Stripe).
     */
    public function sincronizarCantidadSuscripcion(): void
    {
        if (! $this->subscribed('default')) {
            return;
        }

        $tarifa = Tarifa::actual();

        if (! $tarifa->stripe_price_extra_id) {
            return;
        }

        $extra = $tarifa->empleadosExtra($this->usuarios()->where('activo', true)->count());
        $suscripcion = $this->subscription('default');
        $tienePrecioExtra = $suscripcion->hasPrice($tarifa->stripe_price_extra_id);

        if ($extra > 0 && $tienePrecioExtra) {
            $suscripcion->updateQuantity($extra, $tarifa->stripe_price_extra_id);
        } elseif ($extra > 0 && ! $tienePrecioExtra) {
            $suscripcion->addPrice($tarifa->stripe_price_extra_id, $extra);
        } elseif ($extra === 0 && $tienePrecioExtra) {
            $suscripcion->removePrice($tarifa->stripe_price_extra_id);
        }
    }
}
