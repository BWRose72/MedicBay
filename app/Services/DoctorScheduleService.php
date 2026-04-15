<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorTimeOff;
use App\Models\DoctorWorkingHour;
use App\Models\Patient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DoctorScheduleService
{
    public function slotsForDate(int $doctorId, CarbonImmutable $date): Collection
    {
        $timezone = $this->timezone();
        $date = $date->setTimezone($timezone)->startOfDay();

        $doctor = Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        $workingHours = $this->workingHoursForDoctorAndDate((int) $doctor->doctor_id, $date);

        if ($workingHours->isEmpty()) {
            return collect();
        }

        $dayStart = $date->startOfDay();
        $dayEnd = $date->endOfDay();
        $now = CarbonImmutable::now($timezone);

        $appointmentsByStart = Appointment::query()
            ->with(['patient' => function ($q) {
                $q->withoutTrashed();
            }])
            ->where('doctor_id', (int) $doctor->doctor_id)
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->where('status', AppointmentStatus::Scheduled)
            ->get()
            ->keyBy(fn (Appointment $appointment) => CarbonImmutable::parse($appointment->start_time, $timezone)->format('Y-m-d H:i:s'));

        $timeOffs = $this->timeOffsForDoctor((int) $doctor->doctor_id);
        $slots = collect();

        foreach ($workingHours as $workingHour) {
            foreach ($this->slotsFromWorkingHourRow($date, $workingHour) as $slot) {
                if ($this->isSlotInsideTimeOff($slot['start'], $slot['end'], $timeOffs)) {
                    continue;
                }

                $startKey = $slot['start']->format('Y-m-d H:i:s');
                $appointment = $appointmentsByStart->get($startKey);
                $isTaken = $appointment instanceof Appointment;
                $isBookable = ! $isTaken && $now->lt($slot['start']->subHours(2));

                $slots->push([
                    'appointment_id' => $appointment ? (int) $appointment->getKey() : null,
                    'start' => $startKey,
                    'end' => $slot['end']->format('Y-m-d H:i:s'),
                    'time' => $slot['start']->format('H:i'),
                    'taken' => $isTaken,
                    'bookable' => $isBookable,
                    'patient_id' => $appointment ? (int) $appointment->patient_id : null,
                    'patient_name' => $appointment?->patient?->name,
                    'patient_gender' => $appointment?->patient?->gender,
                    'patient_dob' => $appointment?->patient?->date_of_birth?->format('Y-m-d'),
                    'patient_phone' => $appointment?->patient?->phone,
                    'patient_mrn' => $appointment?->patient?->medical_record_number,
                ]);
            }
        }

        return $slots->values();
    }

    public function freeSlotsForDate(int $doctorId, CarbonImmutable $date): Collection
    {
        return $this->slotsForDate($doctorId, $date)
            ->filter(fn (array $slot) => ! $slot['taken'])
            ->map(fn (array $slot) => [
                'start' => $slot['start'],
                'end' => $slot['end'],
            ])
            ->values();
    }

    public function bookSlot(User $actor, int $doctorId, CarbonImmutable $slotStart): Appointment
    {
        $timezone = $this->timezone();

        if (! $actor->can('schedule.book')) {
            throw new AuthorizationException('Only patients can book appointments.');
        }

        $doctor = Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        $patient = Patient::query()
            ->withoutTrashed()
            ->where('user_id', (int) $actor->getKey())
            ->firstOrFail();

        $slotStart = $slotStart->setTimezone($timezone)->seconds(0);
        $slotEnd = $slotStart->addMinutes(30);
        $now = CarbonImmutable::now($timezone);

        if ($now->greaterThanOrEqualTo($slotStart->subHours(2))) {
            throw new InvalidArgumentException('Appointments can only be booked more than 2 hours before the slot starts.');
        }

        $this->assertSlotIsWithinWorkingHours((int) $doctor->doctor_id, $slotStart);
        $this->assertSlotIsNotInTimeOff((int) $doctor->doctor_id, $slotStart, $slotEnd);

        return DB::transaction(function () use ($doctor, $patient, $slotStart) {
            $exists = Appointment::query()
                ->where('doctor_id', (int) $doctor->doctor_id)
                ->where('start_time', $slotStart)
                ->where('status', AppointmentStatus::Scheduled)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException('This slot is no longer available.');
            }

            return Appointment::create([
                'doctor_id' => (int) $doctor->doctor_id,
                'patient_id' => (int) $patient->patient_id,
                'start_time' => $slotStart,
                'has_left_review' => false,
                'status' => AppointmentStatus::Scheduled,
            ]);
        });
    }

    private function workingHoursForDoctorAndDate(int $doctorId, CarbonImmutable $date): Collection
    {
        $iso = $date->dayOfWeekIso;

        return DoctorWorkingHour::query()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $iso)
            ->orderBy('start_time')
            ->get();
    }

    private function slotsFromWorkingHourRow(CarbonImmutable $date, DoctorWorkingHour $workingHour): array
    {
        $start = $this->combineDateAndTime($date, CarbonImmutable::parse($workingHour->start_time));
        $end = $this->combineDateAndTime($date, CarbonImmutable::parse($workingHour->end_time));

        if ($end->lessThanOrEqualTo($start)) {
            return [];
        }

        $slots = [];
        $cursor = $start->seconds(0);

        while (true) {
            $next = $cursor->addMinutes(30);

            if ($next->greaterThan($end)) {
                break;
            }

            $slots[] = ['start' => $cursor, 'end' => $next];
            $cursor = $next;

            if ($cursor->equalTo($end)) {
                break;
            }
        }

        return $slots;
    }

    private function timeOffsForDoctor(int $doctorId): Collection
    {
        return DoctorTimeOff::query()
            ->where('doctor_id', $doctorId)
            ->get();
    }

    private function combineDateAndTime(CarbonImmutable $date, CarbonImmutable $time): CarbonImmutable
    {
        return $date
            ->setTimezone($this->timezone())
            ->setTime((int) $time->format('H'), (int) $time->format('i'), 0);
    }

    private function isSlotInsideTimeOff(CarbonImmutable $slotStart, CarbonImmutable $slotEnd, Collection $timeOffs): bool
    {
        foreach ($timeOffs as $timeOff) {
            $timeOffStart = $this->combineDateAndTime($slotStart, CarbonImmutable::parse($timeOff->start_time));
            $timeOffEnd = $this->combineDateAndTime($slotStart, CarbonImmutable::parse($timeOff->end_time));

            if ($slotStart->lt($timeOffEnd) && $slotEnd->gt($timeOffStart)) {
                return true;
            }
        }

        return false;
    }

    private function assertSlotIsWithinWorkingHours(int $doctorId, CarbonImmutable $slotStart): void
    {
        $date = $slotStart->startOfDay();
        $workingHours = $this->workingHoursForDoctorAndDate($doctorId, $slotStart);

        if ($workingHours->isEmpty()) {
            throw new InvalidArgumentException('Doctor has no working hours for this day.');
        }

        $slotEnd = $slotStart->addMinutes(30);

        foreach ($workingHours as $workingHour) {
            $start = $this->combineDateAndTime($date, CarbonImmutable::parse($workingHour->start_time));
            $end = $this->combineDateAndTime($date, CarbonImmutable::parse($workingHour->end_time));

            if ($slotStart->greaterThanOrEqualTo($start) && $slotEnd->lessThanOrEqualTo($end)) {
                return;
            }
        }

        throw new InvalidArgumentException('Requested slot is outside doctor working hours.');
    }

    private function assertSlotIsNotInTimeOff(int $doctorId, CarbonImmutable $slotStart, CarbonImmutable $slotEnd): void
    {
        $timeOffs = $this->timeOffsForDoctor($doctorId);

        if ($this->isSlotInsideTimeOff($slotStart, $slotEnd, $timeOffs)) {
            throw new InvalidArgumentException('Requested slot is during doctor time off.');
        }
    }

    private function timezone(): string
    {
        return (string) config('app.timezone', 'Europe/Sofia');
    }
}
