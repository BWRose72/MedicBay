<?php

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

Route::get('/doctors', function () {
    return Inertia::render('Doctors');
})->name('doctors');
Route::get('/make-appointment', function () {
    return Inertia::render('MakeAppointment');
})->name('make-appointment');

require __DIR__.'/settings.php';
