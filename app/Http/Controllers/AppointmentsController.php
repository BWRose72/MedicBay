<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\DoctorScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class AppointmentsController extends Controller
{
    public function store(Request $request, int $doctor_id, DoctorScheduleService $doctorScheduleService): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'start' => ['required', 'date'],
        ]);

        try {
            $doctorScheduleService->bookSlot(
                $user,
                $doctor_id,
                CarbonImmutable::parse((string) $validated['start'], (string) config('app.timezone', 'Europe/Sofia')),
            );
        } catch (AuthorizationException) {
            abort(Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'slot' => $exception->getMessage(),
            ]);
        }

        return back();
    }

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
