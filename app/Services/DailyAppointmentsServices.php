<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

final class DailyAppointmentsServices
{
    /**
     * Get all scheduled future appointments for a doctor.
     *
     * @return Collection<int, array{
     *     appointment_id: int,
     *     start_time: string|null,
     *     patient_id: int,
     *     patient_name: string|null,
     *     patient_gender: mixed,
     *     patient_age: mixed
     * }>
     */
    public function upcomingForDoctor(User $actor, int $doctorId): Collection
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('Only doctors (or admins) can see upcoming doctor appointments.');
        }

        Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        $timezone = (string) config('app.timezone', 'Europe/Sofia');

        return Appointment::query()
            ->with(['patient' => function ($q) {
                $q->withoutTrashed();
            }, 'patient.user'])
            ->forDoctor($doctorId)
            ->upcoming()
            ->where('status', AppointmentStatus::Scheduled)
            ->orderBy('start_time')
            ->get()
            ->map(function (Appointment $appointment) use ($timezone) {
                return $this->appointmentPayload($appointment, $timezone);
            })
            ->values();
    }

    /**
     * Get all scheduled appointments for a doctor on a specific date.
     *
     * @return Collection<int, array{
     *     appointment_id: int,
     *     start_time: string|null,
     *     patient_id: int,
     *     patient_name: string|null,
     *     patient_gender: mixed,
     *     patient_age: mixed
     * }>
     */
    public function forDoctorOnDate(User $actor, int $doctorId, CarbonImmutable $date): Collection
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('Only doctors (or admins) can see doctor\'s daily appointments.');
        }

        Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        $timezone = (string) config('app.timezone', 'Europe/Sofia');
        $date = $date->setTimezone($timezone);

        return Appointment::query()
            ->with(['patient' => function ($q) {
                $q->withoutTrashed();
            }, 'patient.user'])
            ->forDoctor($doctorId)
            ->where('status', AppointmentStatus::Scheduled)
            ->whereBetween('start_time', [$date->startOfDay(), $date->endOfDay()])
            ->orderBy('start_time')
            ->get()
            ->map(function (Appointment $appointment) use ($timezone) {
                return $this->appointmentPayload($appointment, $timezone);
            })
            ->values();
    }

    /**
     * Build the response payload for a doctor's appointment list.
     *
     * @return array{
     *     appointment_id: int,
     *     start_time: string|null,
     *     patient_id: int,
     *     patient_name: string|null,
     *     patient_gender: mixed,
     *     patient_age: mixed
     * }
     */
    private function appointmentPayload(Appointment $appointment, string $timezone): array
    {
        return [
            'appointment_id' => (int) $appointment->getKey(),
            'start_time' => $appointment->start_time
                ? CarbonImmutable::parse($appointment->start_time, $timezone)->format('Y-m-d H:i:s')
                : null,
            'patient_id' => (int) $appointment->patient_id,
            'patient_name' => $appointment->patient?->name,
            'patient_gender' => $appointment->patient?->gender,
            'patient_age' => $appointment->patient?->age,
        ];
    }
}
