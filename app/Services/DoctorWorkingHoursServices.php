<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DoctorWorkingHour;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DoctorWorkingHoursServices
{
    public function intervals30Min(int $workingHoursId): Collection
    {
        $shift = $this->findOrFail($workingHoursId);

        $start = $this->parseTimeToCarbon((string) $shift->start_time);
        $end = $this->parseTimeToCarbon((string) $shift->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            return collect();
        }

        $slots = collect();
        $cursor = $start;

        while (true) {
            $next = $cursor->addMinutes(30);

            if ($next->greaterThan($end)) {
                break;
            }

            $slots->push([
                'start' => $cursor->format('H:i'),
                'end' => $next->format('H:i'),
            ]);

            $cursor = $next;

            if ($cursor->equalTo($end)) {
                break;
            }
        }

        return $slots;
    }

    public function create(User $actor, int $doctorId, array $attributes): DoctorWorkingHour
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $data = $this->normalizeAttributes($attributes, $doctorId);

        $workingHours = new DoctorWorkingHour;
        $workingHours->fill($data);
        $workingHours->save();

        return $workingHours;
    }

    public function update(User $actor, int $workingHoursId, array $attributes): DoctorWorkingHour
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $workingHours = $this->findOrFail($workingHoursId);

        $data = $this->normalizeAttributes($attributes, (int) $workingHours->doctor_id);

        $workingHours->fill($data);
        $workingHours->save();

        return $workingHours;
    }

    public function delete(User $actor, int $workingHoursId): void
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $workingHours = $this->findOrFail($workingHoursId);

        $workingHours->delete();
    }

    public function replaceSchedule(User $actor, int $doctorId, array $entries): Collection
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        return DB::transaction(function () use ($doctorId, $entries) {
            DoctorWorkingHour::query()
                ->where('doctor_id', $doctorId)
                ->delete();

            $created = collect();

            foreach ($entries as $attributes) {
                $data = $this->normalizeAttributes($attributes, $doctorId);

                $workingHours = new DoctorWorkingHour;
                $workingHours->fill($data);
                $workingHours->save();

                $created->push($workingHours);
            }

            return $created
                ->sortBy([
                    fn ($a, $b) => $a->day_of_week <=> $b->day_of_week,
                    fn ($a, $b) => strcmp((string) $a->start_time, (string) $b->start_time),
                ])
                ->values();
        });
    }

    public function findOrFail(int $workingHoursId): DoctorWorkingHour
    {
        return DoctorWorkingHour::query()
            ->whereKey($workingHoursId)
            ->firstOrFail();
    }

    private function normalizeAttributes(array $attributes, int $doctorId): array
    {
        $attributes['doctor_id'] = $doctorId;

        return $attributes;
    }

    private function parseTimeToCarbon(string $time): CarbonImmutable
    {
        $parsed = CarbonImmutable::createFromFormat('H:i:s', $time);

        if ($parsed !== false) {
            return $parsed;
        }

        $parsed = CarbonImmutable::createFromFormat('H:i', $time);

        if ($parsed !== false) {
            return $parsed;
        }
        
        throw new \InvalidArgumentException("Invalid time format: {$time}");
    }
}
