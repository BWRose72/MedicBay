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
            ->map(function (Appointment $a) {
                return [
                    'appointment_id' => (int) $a->getKey(),
                    'start_time' => $a->start_time ? CarbonImmutable::parse($a->start_time, $timezone)->format('Y-m-d H:i:s') : null,
                    'patient_id' => (int) $a->patient_id,
                    'patient_name' => $a->patient?->name,
                    'patient_gender' => $a->patient?->gender,
                    'patient_age' => $a->patient?->age,
                ];
            })
            ->values();
    }

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
        $start = $date->startOfDay();
        $end = $date->endOfDay();

        return Appointment::query()
            ->with(['patient' => function ($q) {
                $q->withoutTrashed();
            }, 'patient.user'])
            ->forDoctor($doctorId)
            ->where('status', AppointmentStatus::Scheduled)
            ->whereBetween('start_time', [$start, $end])
            ->orderBy('start_time')
            ->get()
            ->map(function (Appointment $a) {
                return [
                    'appointment_id' => (int) $a->getKey(),
                    'start_time' => $a->start_time ? CarbonImmutable::parse($a->start_time, $timezone)->format('Y-m-d H:i:s') : null,
                    'patient_id' => (int) $a->patient_id,
                    'patient_name' => $a->patient?->name,
                    'patient_gender' => $a->patient?->gender,
                    'patient_age' => $a->patient?->age,
                ];
            })
            ->values();
    }
}
