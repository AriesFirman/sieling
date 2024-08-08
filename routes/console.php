<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\BotController;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Artisan::command('tacticalpro_absensi {start_date} {end_date}', function ($start_date, $end_date) {
    ApiController::tacticalpro_absensi($start_date, $end_date);
});
Artisan::command('portal_report_hr {user} {pass} {unit} {start_date} {end_date}', function ($user, $pass, $unit, $start_date, $end_date) {
    ApiController::portal_report_hr($user, $pass, $unit, $start_date, $end_date);
});

Artisan::command('update_webhook', function () {
    BotController::update_webhook();
});
