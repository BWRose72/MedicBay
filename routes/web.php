<?php

use App\Http\Controllers\Admin\AllUsersController;
use App\Http\Controllers\AppointmentsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorsController;
use App\Http\Controllers\PatientsController;
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

Route::patch('/appointments/{appointment}/patient-cancel', [AppointmentsController::class, 'patientCancel'])
    ->middleware(['auth'])
    ->name('appointments.patientCancel');

Route::post('/appointments/{appointment}/review', [AppointmentsController::class, 'leaveReview'])
    ->middleware(['auth'])
    ->name('appointments.leaveReview');

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

Route::post('/doctors/{doctor_id}/appointments', [AppointmentsController::class, 'store'])
    ->middleware(['auth'])
    ->name('appointments.store');

Route::get('/doctors/{doctor_id}/edit', [DoctorsController::class, 'edit'])
    ->middleware(['auth'])
    ->name('doctors.edit');

Route::patch('/doctors/{doctor_id}', [DoctorsController::class, 'update'])
    ->middleware(['auth'])
    ->name('doctors.update');

Route::patch('/doctors/{doctor_id}/schedule', [DoctorsController::class, 'updateSchedule'])
    ->middleware(['auth'])
    ->name('doctors.schedule.update');

Route::patch('/doctors/{doctor_id}/time-offs', [DoctorsController::class, 'updateTimeOffs'])
    ->middleware(['auth'])
    ->name('doctors.time-offs.update');

Route::post('/doctors/{doctor_id}/photo', [DoctorsController::class, 'updatePhoto'])
    ->middleware(['auth'])
    ->name('doctors.photo.update');

Route::get('/patients/{patient_id}', [PatientsController::class, 'show'])
    ->middleware(['auth'])
    ->name('patients.show');

Route::get('/patients/{patient_id}/edit', [PatientsController::class, 'edit'])
    ->middleware(['auth'])
    ->name('patients.edit');

Route::patch('/patients/{patient_id}', [PatientsController::class, 'update'])
    ->middleware(['auth'])
    ->name('patients.update');

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

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

require __DIR__.'/settings.php';
