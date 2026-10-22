<?php

return [
    // Clave de IndexNow (8-128 caracteres hex). Si no se define INDEXNOW_KEY, se deriva de
    // APP_KEY, así es estable entre despliegues y no hay que guardar nada más.
    'indexnow_key' => env('INDEXNOW_KEY') ?: substr(hash('sha256', 'indexnow|'.env('APP_KEY')), 0, 32),
];
