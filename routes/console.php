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
