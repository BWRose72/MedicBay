<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
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

        $isPatientRole = $user && method_exists($user, 'hasRole') && $user->hasRole('patient');
        $isDoctorRole = $user && method_exists($user, 'hasRole') && $user->hasRole('doctor');
        $isAdminRole = $user && method_exists($user, 'hasRole') && $user->hasRole('admin');

        if ($isPatientRole) {
            $patient = Patient::query()
                ->withoutTrashed()
                ->where('user_id', (int) $user->getKey())
                ->first();

            if (!$patient) {
                return Inertia::render('Dashboard', [
                    'dashboard_type' => 'default',
                    'notice' => 'Patient profile not found for this account.',
                ]);
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
                        !$hasLeftReview &&
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
                return $t->lessThan($weekStart);
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

        if ($isDoctorRole) {
            $doctor = Doctor::query()
                ->withoutTrashed()
                ->where('user_id', (int) $user->getKey())
                ->first();

            if (!$doctor) {
                return Inertia::render('Dashboard', [
                    'dashboard_type' => 'default',
                    'notice' => 'Doctor profile not found for this account.',
                ]);
            }

            $now = CarbonImmutable::now();
            $start = $now->startOfDay();
            $end = $now->endOfDay();

            $today = Appointment::query()
                ->with(['patient' => function ($q) {
                    $q->withoutTrashed();
                }])
                ->forDoctor((int) $doctor->doctor_id)
                ->whereBetween('start_time', [$start, $end])
                ->where('status', AppointmentStatus::Scheduled)
                ->orderBy('start_time')
                ->get()
                ->map(function (Appointment $a) {
                    $start = CarbonImmutable::parse($a->start_time);
                    $endsAt = CarbonImmutable::parse($a->ends_at);

                    return [
                        'appointment_id' => (int) $a->getKey(),
                        'start_time' => $start->format('Y-m-d H:i'),
                        'time' => $start->format('H:i'),
                        'ends_at' => $endsAt->format('Y-m-d H:i'),
                        'patient_name' => (string) ($a->patient?->name ?? 'Patient'),
                        'patient_gender' => (string) ($a->patient?->gender ?? ''),
                        'patient_age' => $a->patient?->age,
                    ];
                })
                ->values();

            $past = $today->filter(function (array $row) use ($now) {
                $endsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['ends_at']);
                return $endsAt->lessThanOrEqualTo($now);
            })->values();

            $future = $today->filter(function (array $row) use ($now) {
                $start = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['start_time']);
                return $start->greaterThan($now);
            })->values();

            $current = $today->first(function (array $row) use ($now) {
                $start = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['start_time']);
                $endsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $row['ends_at']);
                return $now->greaterThanOrEqualTo($start) && $now->lessThan($endsAt);
            });

            return Inertia::render('Dashboard', [
                'dashboard_type' => 'doctor',
                'appointments' => [
                    'past' => $past,
                    'current' => $current,
                    'future' => $future,
                ],
            ]);
        }

        if ($isAdminRole) {
            return Inertia::render('Dashboard', [
                'dashboard_type' => 'admin',
            ]);
        }

        return Inertia::render('Dashboard', [
            'dashboard_type' => 'default',
        ]);
    }
}