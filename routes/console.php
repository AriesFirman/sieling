<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ApiController;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Artisan::command('tacticalpro_absensi {start_date} {end_date}', function ($start_date, $end_date) {
    ApiController::tacticalpro_absensi($start_date, $end_date);
});
