<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'dni_nie' => null,
            'rol' => 'empleado',
            'activo' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    public function adminEmpresa(): static
    {
        return $this->state(fn () => ['rol' => 'admin_empresa']);
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['empresa_id' => null, 'rol' => 'super_admin']);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
