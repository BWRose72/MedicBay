<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

final class DoctorServices
{
    public function all(): Collection
    {
        return Doctor::query()
            ->withoutTrashed()
            ->get();
    }

    public function allBySpecialisationId(int $specialisationId): Collection
    {
        return Doctor::query()
            ->withoutTrashed()
            ->where('specialisation_id', $specialisationId)
            ->get();
    }

    public function findOrFail(int $id): Doctor
    {
        return Doctor::query()
            ->withoutTrashed()
            ->whereKey($id)
            ->firstOrFail();
    }

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

    public function delete(User $actor, int $id): void
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $doctor = $this->findOrFail($id);
        $doctor->delete();
    }
}
