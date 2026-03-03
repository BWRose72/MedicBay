<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DoctorWorkingHour;
use App\Services\DoctorServices;
use App\Services\DoctorWorkingHoursServices;
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
                'specialisation_id' => (int) $s->specialisation_id,
                'name' => (string) $s->name,
            ])
            ->values();

        $doctors = $selectedSpecialisationId
            ? $doctorServices->allBySpecialisationId($selectedSpecialisationId)
            : $doctorServices->all();

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

    public function show(
        int $doctor_id,
        DoctorServices $doctorServices,
        DoctorWorkingHoursServices $doctorWorkingHoursService,
    ): Response {
        $doctor = $doctorServices->findOrFail($doctor_id);
        $doctor->load('specialisation');

        $workingHours = DoctorWorkingHour::query()
            ->where('doctor_id', $doctor_id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Synthesize 30-min appointment slots from each working-hours row
        $slots = $workingHours->map(function (DoctorWorkingHour $wh) use ($doctorWorkingHoursService) {
            return [
                'working_hours_id' => (int) $wh->id,
                'day_of_week' => (int) $wh->day_of_week,
                'start_time' => (string) $wh->start_time,
                'end_time' => (string) $wh->end_time,
                'intervals' => $doctorWorkingHoursService->intervals30Min((int) $wh->id)->values(),
            ];
        })->values();

        return Inertia::render('Doctors/Show', [
            'doctor' => [
                'doctor_id' => (int) $doctor->doctor_id,
                'name' => (string) $doctor->name,
                'display_name' => (string) $doctor->display_name,
                'specialisation' => [
                    'specialisation_id' => (int) ($doctor->specialisation?->specialisation_id ?? 0),
                    'name' => (string) ($doctor->specialisation?->name ?? ''),
                ],
                'phone' => (string) ($doctor->phone ?? ''),
                'bio' => (string) ($doctor->bio ?? ''),
            ],
            'slots' => $slots, // placeholder until i figure out how to add the slots
        ]);
    }
}
