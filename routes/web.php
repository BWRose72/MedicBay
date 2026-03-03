<?php

use App\Http\Controllers\DoctorsController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('/doctors', [DoctorsController::class, 'index'])->name('doctors.index');

Route::get('/doctors/{doctor_id}', [DoctorsController::class, 'show'])
    ->middleware(['auth'])
    ->name('doctors.show');

require __DIR__.'/settings.php';
