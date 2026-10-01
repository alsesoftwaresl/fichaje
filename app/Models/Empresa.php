<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Empresa extends Model
{
    use Billable, HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'nif',
        'email_contacto',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $empresa) {
            $empresa->kiosko_token ??= Str::random(32);
        });
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function kioskoUrl(): string
    {
        return route('kiosko.show', $this->kiosko_token);
    }

    // Cashier usa name/email por defecto; esta empresa los llama nombre/email_contacto.
    public function stripeName(): ?string
    {
        return $this->nombre;
    }

    public function stripeEmail(): ?string
    {
        return $this->email_contacto;
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
