<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Produção precisa de `schedule:work` ou do cron do scheduler (docs/operacao/producao.md).
Schedule::command('gabaritu:limpar-pendentes')->dailyAt('03:30')->timezone(config('gabaritu.fuso'));
