<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

final class AppointmentsController extends Controller
{
    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if (!$user || !method_exists($user, 'hasRole') || (!$user->hasRole('doctor') && !$user->hasRole('admin'))) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($user->hasRole('doctor')) {
            $doctor = Doctor::query()
                ->withoutTrashed()
                ->where('user_id', (int) $user->getKey())
                ->firstOrFail();

            if ((int) $appointment->doctor_id !== (int) $doctor->doctor_id) {
                abort(Response::HTTP_FORBIDDEN);
            }
        }

        if ($appointment->status !== AppointmentStatus::Scheduled) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only scheduled appointments can be updated.');
        }

        if (!$appointment->ends_at->isPast() && !$appointment->ends_at->isToday()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only past appointments can be marked as Completed or NoShow.');
        }
        if ($appointment->ends_at->isFuture()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only past appointments can be marked as Completed or NoShow.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([AppointmentStatus::Completed->value, AppointmentStatus::NoShow->value])],
        ]);

        $to = AppointmentStatus::from((string) $validated['status']);
        $appointment->transitionTo($to);
        $appointment->save();

        return back();
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if (!$user || !method_exists($user, 'hasRole') || (!$user->hasRole('doctor') && !$user->hasRole('admin'))) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($user->hasRole('doctor')) {
            $doctor = Doctor::query()
                ->withoutTrashed()
                ->where('user_id', (int) $user->getKey())
                ->firstOrFail();

            if ((int) $appointment->doctor_id !== (int) $doctor->doctor_id) {
                abort(Response::HTTP_FORBIDDEN);
            }
        }

        if ($appointment->status !== AppointmentStatus::Scheduled) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only scheduled appointments can be cancelled.');
        }

        if (!$appointment->start_time->isFuture()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only future appointments can be cancelled.');
        }

        $appointment->transitionTo(AppointmentStatus::Cancelled);
        $appointment->save();

        return back();
    }
}