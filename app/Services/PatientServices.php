<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class PatientServices
{
    public function findOrFail(User $actor, int $id): Patient
    {
        if (! $actor->can('patient.view')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        return Patient::query()
            ->withoutTrashed()
            ->whereKey($id)
            ->firstOrFail();
    }

    public function create(User $actor, array $attributes): Patient
    {
        if (! $actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $patient = new Patient;
        $patient->fill($attributes);
        $patient->save();

        return $patient;
    }

    public function update(User $actor, int $id, array $attributes): Patient
    {
        if (! $actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $patient = Patient::query()
            ->withoutTrashed()
            ->whereKey($id)
            ->firstOrFail();

        $patient->fill($attributes);
        $patient->save();

        return $patient;
    }

    public function delete(User $actor, int $id): void
    {
        if (! $actor->can('patient')) {
            throw new AuthorizationException('You are not allowed to perform this action.');
        }

        $patient = Patient::query()
            ->withoutTrashed()
            ->whereKey($id)
            ->firstOrFail();

        $patient->delete();
    }
}
