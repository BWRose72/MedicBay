<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DoctorServices;
use App\Services\ReviewServices;
use App\Services\SpecialisationServices;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DoctorsController extends Controller
{
    public function index(
        Request $request,
        DoctorServices $doctorServices,
        ReviewServices $reviewServices,
        SpecialisationServices $specialisationServices,
    ): Response {
        $selectedSpecialisationId = $request->integer('specialisation_id') ?: null;

        $specialisations = $specialisationServices->all()
            ->map(fn ($s) => [
                // Adjust keys if your model differs
                'specialisation_id' => (int) $s->specialisation_id,
                'name' => (string) $s->name,
            ])
            ->values();

        $doctors = $selectedSpecialisationId
            ? $doctorServices->allBySpecialisationId($selectedSpecialisationId)
            : $doctorServices->all();

        // If you already calculate rating elsewhere, keep your existing logic.
        $payload = $doctors->map(function ($d) use ($reviewServices, $specialisations) {
            $summary = $reviewServices->publicDoctorRatingSummary((int) $d->doctor_id);

            $spec = $specialisations->firstWhere('specialisation_id', (int) $d->specialisation_id);

            return [
                'doctor_id' => (int) $d->doctor_id,
                'name' => (string) $d->name,
                'specialisation_label' => $spec['name'] ?? null,
                'rating' => $summary === null ? null : [
                    'attitude_avg' => $summary['attitude_avg'],
                    'professionalism_avg' => $summary['professionalism_avg'],
                    'reviews_count' => $summary['reviews_count'],
                ],
            ];
        })->values();

        return Inertia::render('Doctors', [
            'doctors' => $payload,
            'specialisations' => $specialisations,
            'selectedSpecialisationId' => $selectedSpecialisationId,
        ]);
    }
}