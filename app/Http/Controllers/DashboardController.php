<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasRole')) {
            return Inertia::render('Dashboard', ['dashboard_type' => 'default']);
        }

        // Priority: admin > doctor > patient
        if ($user->hasRole('admin')) {
            return Inertia::render('Dashboard', ['dashboard_type' => 'admin']);
        }

        if ($user->hasRole('doctor')) {
            return $this->doctorDashboard((int) $user->getKey());
        }

        if ($user->hasRole('patient')) {
            return $this->patientDashboard((int) $user->getKey());
        }

        return Inertia::render('Dashboard', ['dashboard_type' => 'default']);
    }

    private function patientDashboard(int $userId): Response
    {
        $patient = DB::table('patients')
            ->select(['patient_id'])
            ->whereNull('deleted_at')
            ->where('user_id', $userId)
            ->first();

        if (! $patient) {
            return Inertia::render('Dashboard', [
                'dashboard_type' => 'default',
                'notice' => 'Patient profile not found for this account.',
            ]);
        }

        $tz = config('app.timezone', 'Europe/Sofia');
        $now = CarbonImmutable::now($tz);

        $rows = DB::table('appointments')
            ->leftJoin('doctors', 'appointments.doctor_id', '=', 'doctors.doctor_id')
            ->where('appointments.patient_id', (int) $patient->patient_id)
            ->where('appointments.status', '!=', AppointmentStatus::Cancelled->value)
            ->orderBy('appointments.start_time')
            ->get([
                'appointments.appointment_id',
                'appointments.doctor_id',
                'appointments.start_time',
                'appointments.status',
                'appointments.has_left_review',
                'doctors.name as doctor_name',
            ]);

        $past = [];
        $future = [];

        foreach ($rows as $row) {
            $start = CarbonImmutable::parse($row->start_time, $tz);
            $endsAt = $start->addMinutes(30);

            $status = (string) $row->status;
            $hasLeftReview = (bool) $row->has_left_review;

            $canReview =
                $status === 'completed'
                && ! $hasLeftReview
                && $now->greaterThanOrEqualTo($endsAt)
                && $now->lessThanOrEqualTo($endsAt->addWeek());
            $canCancel =
                $status === AppointmentStatus::Scheduled->value
                && $now->lt($start->subHours(3));

            $payload = [
                'appointment_id' => (int) $row->appointment_id,
                'doctor_id' => (int) $row->doctor_id,
                'doctor_name' => $row->doctor_name ? 'Dr. '.(string) $row->doctor_name : 'Doctor profile unavailable',
                'start_time' => $start->format('Y-m-d H:i'),
                'ends_at' => $endsAt->format('Y-m-d H:i'),
                'status' => $status,
                'has_left_review' => $hasLeftReview,
                'can_review' => $canReview,
                'can_cancel' => $canCancel,
            ];

            // Past = ended; Future = not ended (includes "current")
            if ($endsAt->lessThanOrEqualTo($now)) {
                $past[] = $payload;
            } else {
                $future[] = $payload;
            }
        }

        return Inertia::render('Dashboard', [
            'dashboard_type' => 'patient',
            'appointments' => [
                'past' => array_values($past),
                'future' => array_values($future),
            ],
        ]);
    }

    private function doctorDashboard(int $userId): Response
    {
        $doctor = DB::table('doctors')
            ->select(['doctor_id'])
            ->whereNull('deleted_at')
            ->where('user_id', $userId)
            ->first();

        if (! $doctor) {
            return Inertia::render('Dashboard', [
                'dashboard_type' => 'default',
                'notice' => 'Doctor profile not found for this account.',
            ]);
        }

        $tz = config('app.timezone', 'Europe/Sofia');
        $now = CarbonImmutable::now($tz);

        // TODAY ONLY
        $dayStart = $now->startOfDay();
        $dayEnd = $now->endOfDay();

        $raw = DB::table('appointments')
            ->join('patients', function ($join) {
                $join->on('appointments.patient_id', '=', 'patients.patient_id')
                    ->whereNull('patients.deleted_at');
            })
            ->where('appointments.doctor_id', (int) $doctor->doctor_id)
            ->where('appointments.status', '!=', AppointmentStatus::Cancelled->value)
            ->whereBetween('appointments.start_time', [
                $dayStart->toDateTimeString(),
                $dayEnd->toDateTimeString(),
            ])
            ->orderBy('appointments.start_time')
            ->get([
                'appointments.appointment_id',
                'appointments.start_time',
                'appointments.status',
                'patients.name as patient_name',
                'patients.gender as patient_gender',
                'patients.date_of_birth as patient_dob',
            ]);

        $past = [];
        $current = null;
        $future = [];

        foreach ($raw as $row) {
            $start = CarbonImmutable::parse($row->start_time, $tz);
            $endsAt = $start->addMinutes(30);

            $age = null;
            if (! empty($row->patient_dob)) {
                $age = CarbonImmutable::parse($row->patient_dob)->age;
            }

            $payload = [
                'appointment_id' => (int) $row->appointment_id,
                'start_time' => $start->format('Y-m-d H:i'),
                'time' => $start->format('H:i'),
                'ends_at' => $endsAt->format('Y-m-d H:i'),
                'status' => (string) $row->status,
                'patient_name' => (string) $row->patient_name,
                'patient_gender' => (string) $row->patient_gender,
                'patient_age' => $age,
            ];

            if ($endsAt->lessThanOrEqualTo($now)) {
                $past[] = $payload;
            } elseif ($start->lessThanOrEqualTo($now)) {
                $current = $payload;
            } else {
                $future[] = $payload;
            }
        }

        return Inertia::render('Dashboard', [
            'dashboard_type' => 'doctor',
            'appointments' => [
                'past' => array_values($past),
                'current' => $current,
                'future' => array_values($future),
            ],
        ]);
    }
}
