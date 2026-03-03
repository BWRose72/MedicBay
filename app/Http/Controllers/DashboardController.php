<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Spatie roles (admin/doctor/patient)
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('patient')) {
            $patient = Patient::query()
            ->withoutTrashed()
            ->where('user_id', (int) $user->getKey())
            ->first();

        if (!$patient) {
            abort(403, 'Patient profile not found for this account.');
        }

            $now = CarbonImmutable::now();
            $weekStart = $now->startOfWeek();
            $weekEnd = $now->endOfWeek();

            $appointments = Appointment::query()
                ->with(['doctor' => function ($q) {
                    $q->withoutTrashed();
                }])
                ->forPatient((int) $patient->patient_id)
                ->orderByDesc('start_time')
                ->get()
                ->map(function (Appointment $a) use ($now) {
                    $start = CarbonImmutable::parse($a->start_time);
                    $endsAt = CarbonImmutable::parse($a->ends_at);

                    $status = $a->status instanceof AppointmentStatus ? $a->status->value : (string) $a->status;

                    $isCompleted = ($a->status === AppointmentStatus::Completed);
                    $hasLeftReview = (bool) $a->has_left_review;

                    $canReview =
                        $isCompleted &&
                        ! $hasLeftReview &&
                        $now->greaterThanOrEqualTo($endsAt) &&
                        $now->lessThanOrEqualTo($endsAt->addWeek());

                    return [
                        'appointment_id' => (int) $a->getKey(),
                        'doctor_id' => (int) $a->doctor_id,
                        'doctor_name' => (string) ($a->doctor?->display_name ?? $a->doctor?->name ?? 'Doctor'),
                        'start_time' => $start->format('Y-m-d H:i'),
                        'status' => $status,
                        'has_left_review' => $hasLeftReview,
                        'can_review' => $canReview,
                    ];
                })
                ->values();

            $thisWeek = $appointments->filter(function (array $row) use ($weekStart, $weekEnd) {
                $t = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['start_time']);
                return $t->betweenIncluded($weekStart, $weekEnd);
            })->values();

            $past = $appointments->filter(function (array $row) use ($weekStart) {
                $t = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['start_time']);
                return $t->greaterThan($weekStart);
            })->values();

            $future = $appointments->filter(function (array $row) use ($weekEnd) {
                $t = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['start_time']);
                return $t->greaterThan($weekEnd);
            })->values();

            return Inertia::render('Dashboard', [
                'dashboard_type' => 'patient',
                'appointments' => [
                    'past' => $past,
                    'this_week' => $thisWeek,
                    'future' => $future,
                ],
            ]);
        }

        // TODO: doctor/admin dashboards later
        return Inertia::render('Dashboard', [
            'dashboard_type' => 'default',
        ]);
    }
}
