<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Resumen de incidencias a los admins: laborables a las 11:00 (hora de la app).
// Necesita que algo ejecute "schedule:run" cada minuto (cron o tarea de Plesk).
Schedule::command('incidencias:resumen')->weekdays()->at('11:00');

// Los mensajes del formulario de contacto se guardan como máximo 12 meses (política de privacidad).
Schedule::command('contactos:purgar')->monthly();

// Red de seguridad para los correos en cola: si QUEUE_CONNECTION no es "sync" (p. ej. "database")
// y no hay un worker, sin esto los avisos (vacaciones, nóminas, contacto) no saldrían nunca. Cada
// minuto envía lo pendiente y termina en cuanto la cola está vacía (o a los 20 s, para no pasarse
// del tiempo máximo de PHP cuando schedule:run se llama desde la ruta de despliegue).
Schedule::command('queue:work --stop-when-empty --max-time=20 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
