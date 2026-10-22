<?php

return [
    // Super admin inicial que crea el seeder (php artisan db:seed o cmd=db:seed). Se lee de
    // .env aquí, en config, y no en el seeder: con la configuración en caché (php artisan
    // optimize) env() fuera de config/ devuelve null.
    'super_admin' => [
        'email' => env('SUPER_ADMIN_EMAIL', 'super-admin@fichaje-app.test'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],
];
