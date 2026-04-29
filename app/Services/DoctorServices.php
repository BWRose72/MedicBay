<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

final class DoctorServices
{
    /**
     * Get all active doctors with their user relationship loaded.
     *
     * @return Collection<int, Doctor>
     */
    public function all(): Collection
    {
        return Doctor::query()
            ->withoutTrashed()
            ->with('user')
            ->get();
    }

    /**
     * Get all active doctors for a specialisation.
     *
     * @return Collection<int, Doctor>
     */
    public function allBySpecialisationId(int $specialisationId): Collection
    {
        return Doctor::query()
            ->withoutTrashed()
            ->with('user')
            ->where('specialisation_id', $specialisationId)
            ->get();
    }

    /**
     * Find an active doctor by id or fail.
     */
    public function findOrFail(int $id): Doctor
    {
        return Doctor::query()
            ->withoutTrashed()
            ->with('user')
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * Create a doctor record after authorizing the actor.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(User $actor, array $attributes): Doctor
    {
        if (! $actor->can('doctor.create')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $doctor = new Doctor;
        $doctor->fill($attributes);
        $doctor->save();

        return $doctor;
    }

    /**
     * Update a doctor record after authorizing the actor.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(User $actor, int $id, array $attributes): Doctor
    {
        if (! $actor->can('doctor.update')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $doctor = $this->findOrFail($id);
        $doctor->fill($attributes);
        $doctor->save();

        return $doctor;
    }

    /**
     * Soft-delete a doctor record after authorizing the actor.
     */
    public function delete(User $actor, int $id): void
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $doctor = $this->findOrFail($id);
        $doctor->delete();
    }
}
