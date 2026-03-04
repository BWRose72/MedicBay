<?php

use App\Http\Controllers\DoctorsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\Admin\AllUsersController;
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

Route::middleware(['auth', 'role:doctor|admin'])->group(function () {
Route::patch('/appointments/{appointment}/status', [AppointmentsController::class, 'updateStatus'])
        ->name('appointments.updateStatus');

Route::patch('/appointments/{appointment}/cancel', [AppointmentsController::class, 'cancel'])
        ->name('appointments.cancel');
});

Route::get('/doctors', [DoctorsController::class, 'index'])->name('doctors.index');

Route::get('/doctors/{doctor_id}', [DoctorsController::class, 'show'])
    ->middleware(['auth'])
    ->name('doctors.show');

Route::get('/doctors/{doctor_id}/edit', [DoctorsController::class, 'edit'])
    ->middleware(['auth'])
    ->name('doctors.edit');

Route::patch('/doctors/{doctor_id}', [DoctorsController::class, 'update'])
    ->middleware(['auth'])
    ->name('doctors.update');

Route::middleware(['auth', 'role:admin'])->group(function () {
Route::get('/admin/doctors/create', [DoctorsController::class, 'create']);
Route::get('/admin/doctors', [DoctorsController::class, 'indexAdmin']);
});


Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/users', [AllUsersController::class, 'index'])->name('admin.users.index');

Route::patch('/users/{user}/make-doctor', [AllUsersController::class, 'makeDoctor'])
        ->name('admin.users.makeDoctor');

Route::patch('/users/{user}/fire', [AllUsersController::class, 'fire'])
        ->name('admin.users.fire');

Route::delete('/users/{user}', [AllUsersController::class, 'destroy'])
        ->name('admin.users.destroy');
});

require __DIR__.'/settings.php';
