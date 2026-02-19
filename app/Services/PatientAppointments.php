<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class PatientsAppointments
{
    public function upcoming(User $actor, int $patientId): Collection
    {
        if (!$actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        Patient::query()
            ->withoutTrashed()
            ->whereKey($patientId)
            ->firstOrFail();

        $now = CarbonImmutable::now();

        return Appointment::query()
            ->where('patient_id', $patientId)
            ->where('status', AppointmentStatus::Scheduled)
            ->where('start_time', '>', $now)
            ->orderBy('start_time')
            ->get()
            ->map(function (Appointment $a) {
                return [
                    'appointment_id' => (int) $a->getKey(),
                    'doctor_id' => (int) $a->doctor_id,
                    'start_time' => CarbonImmutable::parse($a->start_time)->format('Y-m-d H:i:s'),
                    'status' => $a->status instanceof AppointmentStatus ? $a->status->value : (string) $a->status,
                ];
            })
            ->values();
    }
    
    public function cancelIfMoreThanTwoHoursRemain(User $actor, int $patientId, int $appointmentId): Appointment
    {
        if (!$actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        Patient::query()
            ->withoutTrashed()
            ->whereKey($patientId)
            ->firstOrFail();

        /** @var Appointment $appointment */
        $appointment = Appointment::query()
            ->whereKey($appointmentId)
            ->firstOrFail();

        if ((int) $appointment->patient_id !== $patientId) {
            throw new AuthorizationException('You can only cancel your own appointments.');
        }

        if ($appointment->status !== AppointmentStatus::Scheduled) {
            throw new InvalidArgumentException('Only scheduled appointments can be cancelled.');
        }

        $start = CarbonImmutable::parse($appointment->start_time);
        $now = CarbonImmutable::now();

        if ($start->lessThanOrEqualTo($now)) {
            throw new InvalidArgumentException('You cannot cancel an appointment that has already started or passed.');
        }

        if ($now->greaterThanOrEqualTo($start->subHours(2))) {
            throw new InvalidArgumentException('You can only cancel if more than 2 hours remain before the appointment.');
        }

        $appointment->status = AppointmentStatus::Cancelled;
        $appointment->save();

        return $appointment->refresh();
    }
    
    public function completedUnreviewed(User $actor, int $patientId): Collection
    {
        if (!$actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        Patient::query()
            ->withoutTrashed()
            ->whereKey($patientId)
            ->firstOrFail();

        $now = CarbonImmutable::now();

        return Appointment::query()
            ->where('patient_id', $patientId)
            ->where('status', AppointmentStatus::Completed)
            ->where('has_left_review', false)
            ->where('start_time', '<', $now)
            ->orderByDesc('start_time')
            ->get()
            ->map(function (Appointment $a) {
                return [
                    'appointment_id' => (int) $a->getKey(),
                    'doctor_id' => (int) $a->doctor_id,
                    'start_time' => CarbonImmutable::parse($a->start_time)->format('Y-m-d H:i:s'),
                ];
            })
            ->values();
    }

}
