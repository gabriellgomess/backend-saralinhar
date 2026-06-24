<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Limpa áudios de pareceres expirados diariamente às 02:00
Schedule::command('reports:clean-expired-audios')->dailyAt('02:00');
