<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily: Auto-complete past trips
Schedule::command('plans:auto-complete')->daily();

// Monthly: Reset AI limits (on 1st of month at 00:01)
Schedule::command('limits:reset-monthly')
    ->monthlyOn(1, '00:01')
    ->timezone('Europe/Warsaw');
