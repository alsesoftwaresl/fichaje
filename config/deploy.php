<?php

return [
    // Deja esto vacío siempre en local/.env.example. Solo se rellena en el
    // hosting sin SSH (p. ej. Loading.es), donde /deploy/ejecutar es la
    // única forma de correr "artisan migrate" tras subir código por FTP.
    // Si está vacío, la ruta devuelve 404 siempre — ver DeployController.
    'token' => env('DEPLOY_TOKEN'),
];
