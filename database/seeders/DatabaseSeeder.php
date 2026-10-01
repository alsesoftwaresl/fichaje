<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea el super_admin inicial para poder entrar por primera vez y dar de
     * alta empresas. Las credenciales reales se leen de .env (nunca se
     * comitean) — sin configurar, cae en un valor de desarrollo genérico.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'super-admin@fichaje-app.test')],
            [
                'empresa_id' => null,
                'name' => 'Super Admin',
                'rol' => 'super_admin',
                'activo' => true,
                'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
            ]
        );
    }
}
