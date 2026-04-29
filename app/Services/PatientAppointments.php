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

final class PatientAppointments
{
    /**
     * Get upcoming scheduled appointments for a patient.
     *
     * @return Collection<int, array{
     *     appointment_id: int,
     *     doctor_id: int,
     *     start_time: string,
     *     status: string
     * }>
     */
    public function upcoming(User $actor, int $patientId): Collection
    {
        if (! $actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        Patient::query()
            ->withoutTrashed()
            ->whereKey($patientId)
            ->firstOrFail();

        $timezone = (string) config('app.timezone', 'Europe/Sofia');
        $now = CarbonImmutable::now($timezone);

        return Appointment::query()
            ->where('patient_id', $patientId)
            ->where('status', AppointmentStatus::Scheduled)
            ->where('start_time', '>', $now)
            ->orderBy('start_time')
            ->get()
            ->map(fn (Appointment $appointment) => $this->appointmentPayload($appointment, $timezone, includeStatus: true))
            ->values();
    }

    /**
     * Cancel a scheduled appointment if more than two hours remain.
     */
    public function cancelIfMoreThanTwoHoursRemain(User $actor, int $patientId, int $appointmentId): Appointment
    {
        if (! $actor->can('patient')) {
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

        $timezone = (string) config('app.timezone', 'Europe/Sofia');
        $start = CarbonImmutable::parse($appointment->start_time, $timezone);
        $now = CarbonImmutable::now($timezone);

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

    /**
     * Get completed patient appointments that are still eligible for review.
     *
     * @return Collection<int, array{
     *     appointment_id: int,
     *     doctor_id: int,
     *     start_time: string
     * }>
     */
    public function completedUnreviewed(User $actor, int $patientId): Collection
    {
        if (! $actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        Patient::query()
            ->withoutTrashed()
            ->whereKey($patientId)
            ->firstOrFail();

        $timezone = (string) config('app.timezone', 'Europe/Sofia');
        $now = CarbonImmutable::now($timezone);

        return Appointment::query()
            ->where('patient_id', $patientId)
            ->where('status', AppointmentStatus::Completed)
            ->where('has_left_review', false)
            ->where('start_time', '<', $now)
            ->orderByDesc('start_time')
            ->get()
            ->map(fn (Appointment $appointment) => $this->appointmentPayload($appointment, $timezone))
            ->values();
    }

    /**
     * Build the response payload for a patient appointment list.
     *
     * @return array{
     *     appointment_id: int,
     *     doctor_id: int,
     *     start_time: string,
     *     status?: string
     * }
     */
    private function appointmentPayload(Appointment $appointment, string $timezone, bool $includeStatus = false): array
    {
        $payload = [
            'appointment_id' => (int) $appointment->getKey(),
            'doctor_id' => (int) $appointment->doctor_id,
            'start_time' => CarbonImmutable::parse($appointment->start_time, $timezone)->format('Y-m-d H:i:s'),
        ];

        if ($includeStatus) {
            $payload['status'] = $appointment->status instanceof AppointmentStatus
                ? $appointment->status->value
                : (string) $appointment->status;
        }

        return $payload;
    }
}
