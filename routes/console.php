<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Política de ventanas: cancelación automática de drafts no enviados al cierre.
// Idempotente — solo actúa cuando estamos justo después del cierre de la ventana de submit.
Schedule::command('payment-drafts:cancel-expired')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Recordatorio 2h antes del cierre. Corre cada 5 min para cubrir el rango con margen.
Schedule::command('payment-drafts:notify-closing-soon')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
