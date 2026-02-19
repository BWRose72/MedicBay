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
    public function freeSlotsForDate(int $doctorId, CarbonImmutable $date): Collection
    {
        $doctor = Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        $workingHours = $this->workingHoursForDoctorAndDate($doctor->doctor_id, $date);

        if ($workingHours->isEmpty()) {
            return collect();
        }

        $dayStart = $date->startOfDay();
        $dayEnd = $date->endOfDay();

        $busyStartTimes = Appointment::query()
            ->where('doctor_id', (int) $doctor->doctor_id)
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->where('status', AppointmentStatus::Scheduled)
            ->pluck('start_time')
            ->map(fn ($dt) => CarbonImmutable::parse($dt)->format('Y-m-d H:i:s'))
            ->flip(); // for O(1) membership checks

        $timeOffs = DoctorTimeOff::query()
            ->where('doctor_id', (int) $doctor->doctor_id)
            ->where(function ($q) use ($dayStart, $dayEnd) {
                // Overlap with the day
                $q->whereBetween('start_time', [$dayStart, $dayEnd])
                  ->orWhereBetween('end_time', [$dayStart, $dayEnd])
                  ->orWhere(function ($qq) use ($dayStart, $dayEnd) {
                      $qq->where('start_time', '<=', $dayStart)
                         ->where('end_time', '>=', $dayEnd);
                  });
            })
            ->get();

        $free = collect();

        foreach ($workingHours as $wh) {
            $slots = $this->slotsFromWorkingHourRow($date, $wh);

            foreach ($slots as $slot) {
                $slotStartStr = $slot['start']->format('Y-m-d H:i:s');

                if (isset($busyStartTimes[$slotStartStr])) {
                    continue;
                }

                if ($this->isSlotInsideTimeOff($slot['start'], $slot['end'], $timeOffs)) {
                    continue;
                }

                $free->push([
                    'start' => $slot['start']->format('Y-m-d H:i:s'),
                    'end'   => $slot['end']->format('Y-m-d H:i:s'),
                ]);
            }
        }

        return $free->values();
    }

    public function bookSlot(User $actor, int $doctorId, CarbonImmutable $slotStart): Appointment
    {
        if (!$actor->can('schedule.book')) {
            throw new AuthorizationException('Only patients (or admins) can book appointments.');
        }

        $doctor = Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        $patient = Patient::query()
            ->withoutTrashed()
            ->where('user_id', (int) $actor->getKey())
            ->firstOrFail();

        $slotStart = $slotStart->seconds(0);
        $slotEnd = $slotStart->addMinutes(30);

        // Must be on the doctor's working schedule and not during time off
        $this->assertSlotIsWithinWorkingHours((int) $doctor->doctor_id, $slotStart);
        $this->assertSlotIsNotInTimeOff((int) $doctor->doctor_id, $slotStart, $slotEnd);

        return DB::transaction(function () use ($doctor, $patient, $slotStart) {
            // Lock any existing row at that time (prevents two concurrent bookings most of the time)
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
                'doctor_id'        => (int) $doctor->doctor_id,
                'patient_id'       => (int) $patient->patient_id,
                'start_time'       => $slotStart,
                'has_left_review'  => false,
                'status'           => AppointmentStatus::Scheduled,
            ]);
        });
    }

    private function workingHoursForDoctorAndDate(int $doctorId, CarbonImmutable $date): Collection
    {
        $iso = $date->dayOfWeekIso; // 1..7 (Mon..Sun)
        $zeroBased = $date->dayOfWeek; // 0..6 (Sun..Sat)

        return DoctorWorkingHour::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('day_of_week', [$iso, $zeroBased])
            ->orderBy('start_time')
            ->get();
    }

    private function slotsFromWorkingHourRow(CarbonImmutable $date, DoctorWorkingHour $wh): array
    {
        $start = $this->combineDateAndTime($date, CarbonImmutable::parse($wh->start_time));
        $end = $this->combineDateAndTime($date, CarbonImmutable::parse($wh->end_time));

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

    private function combineDateAndTime(CarbonImmutable $date, CarbonImmutable $time): CarbonImmutable
    {
        return $date->setTime((int) $time->format('H'), (int) $time->format('i'), 0);
    }

    private function isSlotInsideTimeOff(CarbonImmutable $slotStart, CarbonImmutable $slotEnd, Collection $timeOffs): bool
    {
        foreach ($timeOffs as $dto) {
            ///** @var DoctorTimeOff $to */
            $toStart = CarbonImmutable::parse($dto->start_time);
            $toEnd = CarbonImmutable::parse($dto->end_time);

            // Overlap check: [slotStart, slotEnd) overlaps [toStart, toEnd]
            if ($slotStart->lt($toEnd) && $slotEnd->gt($toStart)) {
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

        foreach ($workingHours as $wh) {
            $start = $this->combineDateAndTime($date, CarbonImmutable::parse($wh->start_time));
            $end = $this->combineDateAndTime($date, CarbonImmutable::parse($wh->end_time));

            if ($slotStart->greaterThanOrEqualTo($start) && $slotEnd->lessThanOrEqualTo($end)) {
                return;
            }
        }

        throw new InvalidArgumentException('Requested slot is outside doctor working hours.');
    }

    private function assertSlotIsNotInTimeOff(int $doctorId, CarbonImmutable $slotStart, CarbonImmutable $slotEnd): void
    {
        $timeOffs = DoctorTimeOff::query()
            ->where('doctor_id', $doctorId)
            ->where('start_time', '<', $slotEnd)
            ->where('end_time', '>', $slotStart)
            ->get();

        if ($timeOffs->isNotEmpty()) {
            throw new InvalidArgumentException('Requested slot is during doctor time off.');
        }
    }
}
