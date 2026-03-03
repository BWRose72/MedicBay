<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Specialisation;
use Illuminate\Support\Collection;

final class SpecialisationServices
{
    public function all(): Collection
    {
        return Specialisation::query()
            ->get();
    }
}