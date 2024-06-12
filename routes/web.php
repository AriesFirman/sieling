<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/layouts', function () {
    return view('layouts');
});

Route::prefix('api')->group(function () {
    Route::post('/sielling_bot', [BotController::class, 'sielling_bot'])->name('sielling_bot');
});
