<?php

use App\Http\Controllers\DoctorsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/doctors', [DoctorsController::class, 'index'])->name('doctors.index');

Route::get('/doctors/{doctor_id}', [DoctorsController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('doctors.show');

require __DIR__.'/settings.php';
