<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RobotsController;
use Illuminate\Support\Facades\Route;

Route::get('robots.txt', RobotsController::class);

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
