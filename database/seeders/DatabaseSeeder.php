<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Crea el super_admin inicial para poder entrar por primera vez y dar de
     * alta empresas. Las credenciales reales vienen de .env a través de
     * config/plataforma.php (nunca se comitean); sin configurar, cae en un valor
     * de desarrollo genérico.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => config('plataforma.super_admin.email')],
            [
                'empresa_id' => null,
                'name' => 'Super Admin',
                'rol' => 'super_admin',
                'activo' => true,
                'password' => config('plataforma.super_admin.password'),
            ]
        );
    }
}
