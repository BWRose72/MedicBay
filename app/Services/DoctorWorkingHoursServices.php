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
    /**
     * Split a working-hours row into 30-minute time intervals.
     *
     * @return Collection<int, array{start: string, end: string}>
     */
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

    /**
     * Create working hours for a doctor after authorizing the actor.
     *
     * @param array<string, mixed> $attributes
     */
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

    /**
     * Update a working-hours row after authorizing the actor.
     *
     * @param array<string, mixed> $attributes
     */
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

    /**
     * Delete a working-hours row after authorizing the actor.
     */
    public function delete(User $actor, int $workingHoursId): void
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $workingHours = $this->findOrFail($workingHoursId);

        $workingHours->delete();
    }

    /**
     * Replace all working-hours entries for a doctor.
     *
     * @param array<int, array<string, mixed>> $entries
     *
     * @return Collection<int, DoctorWorkingHour>
     */
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

    /**
     * Find a working-hours row by id or fail.
     */
    public function findOrFail(int $workingHoursId): DoctorWorkingHour
    {
        return DoctorWorkingHour::query()
            ->whereKey($workingHoursId)
            ->firstOrFail();
    }

    /**
     * Ensure working-hours attributes are assigned to the given doctor.
     *
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes, int $doctorId): array
    {
        $attributes['doctor_id'] = $doctorId;

        return $attributes;
    }

    /**
     * Parse an HH:MM or HH:MM:SS value into an immutable Carbon instance.
     */
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
