<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\DoctorScheduleService;
use App\Services\ReviewServices;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class AppointmentsController extends Controller
{
    /**
     * Book an appointment slot for the authenticated patient.
     */
    public function store(Request $request, int $doctor_id, DoctorScheduleService $doctorScheduleService): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
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

    /**
     * Update an appointment status as an authorized doctor or admin.
     */
    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasRole') || (! $user->hasRole('doctor') && ! $user->hasRole('admin'))) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $isDoctor = $user->hasRole('doctor');
        $isAdmin = $user->hasRole('admin');
        $timezone = (string) config('app.timezone', 'Europe/Sofia');
        $start = CarbonImmutable::parse($appointment->start_time, $timezone);
        $endsAt = CarbonImmutable::parse($appointment->ends_at, $timezone);

        if ($isDoctor && ! $isAdmin) {
            $doctor = Doctor::query()
                ->withoutTrashed()
                ->where('user_id', (int) $user->getKey())
                ->firstOrFail();

            if ((int) $appointment->doctor_id !== (int) $doctor->doctor_id) {
                abort(Response::HTTP_FORBIDDEN);
            }

            if ($appointment->status !== AppointmentStatus::Scheduled) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'You can only set the status once for a scheduled appointment.');
            }

            if (! $start->isToday() || $endsAt->isFuture()) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Doctors can only set status for today\'s past appointments.');
            }
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                AppointmentStatus::Completed->value,
                AppointmentStatus::Cancelled->value,
                AppointmentStatus::NoShow->value,
            ])],
        ]);

        $to = AppointmentStatus::from((string) $validated['status']);

        if ($isDoctor && ! $isAdmin) {
            $appointment->transitionTo($to);
        } else {
            $appointment->status = $to;
        }

        $appointment->save();

        return back();
    }

    /**
     * Cancel a future scheduled appointment as the assigned doctor or an admin.
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasRole') || (! $user->hasRole('doctor') && ! $user->hasRole('admin'))) {
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

        if ($appointment->status === AppointmentStatus::Cancelled) {
            return back();
        }

        if ($appointment->status !== AppointmentStatus::Scheduled) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only scheduled appointments can be cancelled.');
        }

        if (! $appointment->start_time->isFuture()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only future appointments can be cancelled.');
        }

        $appointment->transitionTo(AppointmentStatus::Cancelled);
        $appointment->save();

        return back();
    }

    /**
     * Cancel a patient's own appointment before the cancellation cutoff.
     */
    public function patientCancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('patient')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $patient = Patient::query()
            ->withoutTrashed()
            ->where('user_id', (int) $user->getKey())
            ->firstOrFail();

        if ((int) $appointment->patient_id !== (int) $patient->patient_id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($appointment->status === AppointmentStatus::Cancelled) {
            return back();
        }

        if ($appointment->status !== AppointmentStatus::Scheduled) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Only scheduled appointments can be cancelled.');
        }

        $timezone = (string) config('app.timezone', 'Europe/Sofia');
        $start = CarbonImmutable::parse($appointment->start_time, $timezone);
        $now = CarbonImmutable::now($timezone);

        if ($now->greaterThanOrEqualTo($start->subHours(3))) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Appointments can only be cancelled at least 3 hours before they start.');
        }

        $appointment->transitionTo(AppointmentStatus::Cancelled);
        $appointment->save();

        return back();
    }

    /**
     * Submit a patient review for a completed appointment.
     */
    public function leaveReview(Request $request, Appointment $appointment, ReviewServices $reviewServices): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'attitude' => ['required', 'integer', 'min:1', 'max:10'],
            'professionalism' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $reviewServices->leaveReview(
                actor: $user,
                appointmentId: (int) $appointment->getKey(),
                attitude: (int) $validated['attitude'],
                professionalism: (int) $validated['professionalism'],
            );
        } catch (AuthorizationException) {
            abort(Response::HTTP_FORBIDDEN);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'review' => $exception->getMessage(),
            ]);
        }

        return back();
    }
}
