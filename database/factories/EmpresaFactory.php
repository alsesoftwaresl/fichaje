<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Empresa>
 */
class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->company(),
            'nif' => fake()->unique()->bothify('########?'),
            'email_contacto' => fake()->unique()->companyEmail(),
            'activa' => true,
        ];
    }

    public function configure(): static
    {
        // Toda empresa de prueba nace con una suscripción activa, para que
        // el middleware "suscripcion" no bloquee los tests existentes (que
        // no la provisionan explícitamente). El estado sinSuscripcion()
        // la quita para los tests que sí necesitan probar el bloqueo.
        return $this->afterCreating(function (Empresa $empresa) {
            $empresa->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'sub_fake_'.fake()->unique()->bothify('??????????'),
                'stripe_status' => 'active',
                'stripe_price' => 'price_fake_base',
                'quantity' => 1,
            ]);
        });
    }

    public function sinSuscripcion(): static
    {
        return $this->afterCreating(function (Empresa $empresa) {
            $empresa->subscriptions()->delete();
        });
    }
}
