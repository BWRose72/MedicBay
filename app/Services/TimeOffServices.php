<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorTimeOff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class DoctorTimeOffService
{
    public function createTimeOffAndCancelAppointments(
        User $actor,
        int $doctorId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): DoctorTimeOff {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('Only doctors (or admins) can create time off.');
        }

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Time off end must be after start.');
        }

        $doctor = Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        return DB::transaction(function () use ($doctor, $start, $end) {
            $timeOff = DoctorTimeOff::create([
                'doctor_id' => (int) $doctor->doctor_id,
                'start_time' => $start,
                'end_time' => $end,
            ]);

            $this->cancelAndNotifyOverlappingAppointments(
                doctor: $doctor,
                start: $start,
                end: $end,
            );

            return $timeOff;
        });
    }

    public function updateTimeOffAndCancelAppointments(
        User $actor,
        int $timeOffId,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): DoctorTimeOff {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('Only doctors (or admins) can update time off.');
        }

        $timeOff = DoctorTimeOff::query()->whereKey($timeOffId)->firstOrFail();

        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Time off end must be after start.');
        }

        $doctor = Doctor::query()
            ->withoutTrashed()
            ->whereKey((int) $timeOff->doctor_id)
            ->firstOrFail();

        return DB::transaction(function () use ($timeOff, $doctor, $start, $end) {
            $timeOff->start_time = $start;
            $timeOff->end_time = $end;
            $timeOff->save();

            $this->cancelAndNotifyOverlappingAppointments(
                doctor: $doctor,
                start: $start,
                end: $end,
            );

            return $timeOff->refresh();
        });
    }

    public function deleteTimeOff(User $actor, int $timeOffId): void
    {
        if (! $actor->can('doctor')) {
            throw new AuthorizationException('Only doctors (or admins) can delete time off.');
        }

        $timeOff = DoctorTimeOff::query()->whereKey($timeOffId)->firstOrFail();

        $timeOff->delete();
    }

    private function cancelAndNotifyOverlappingAppointments(
        Doctor $doctor,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): void {
        $appointments = Appointment::query()
            ->with(['patient.user'])
            ->where('doctor_id', (int) $doctor->doctor_id)
            ->where('status', AppointmentStatus::Scheduled)
            ->where('start_time', '<', $end)
            ->whereRaw('DATE_ADD(start_time, INTERVAL 30 MINUTE) > ?', [$start->toDateTimeString()])
            ->lockForUpdate()
            ->get();

        foreach ($appointments as $appointment) {
            $appointment->status = AppointmentStatus::Cancelled;
            $appointment->save();

            $this->sendCancellationEmail(
                doctorName: $this->doctorDisplayName($doctor),
                appointmentStart: CarbonImmutable::parse($appointment->start_time),
                patientEmail: $appointment->patient?->user?->email,
                patientName: $appointment->patient?->name,
                timeOffStart: $start,
                timeOffEnd: $end,
            );
        }
    }

    private function sendCancellationEmail(
        string $doctorName,
        CarbonImmutable $appointmentStart,
        ?string $patientEmail,
        ?string $patientName,
        CarbonImmutable $timeOffStart,
        CarbonImmutable $timeOffEnd,
    ): void {
        if ($patientEmail === null || $patientEmail === '') {
            return;
        }

        $subject = 'Appointment cancelled: doctor unavailable';

        $lines = [];
        $lines[] = ($patientName !== null && $patientName !== '') ? "Hello {$patientName}," : 'Hello,';
        $lines[] = '';
        $lines[] = "Your appointment with {$doctorName} scheduled for {$appointmentStart->format('Y-m-d H:i')} has been cancelled.";
        $lines[] = "Reason: the doctor is unavailable during {$timeOffStart->format('Y-m-d H:i')} to {$timeOffEnd->format('Y-m-d H:i')}.";
        $lines[] = '';
        $lines[] = 'Please log in to select a new available time slot.';

        Mail::raw(implode("\n", $lines), function ($message) use ($patientEmail, $subject) {
            $message->to($patientEmail)->subject($subject);
        });
    }

    private function doctorDisplayName(Doctor $doctor): string
    {
        if (isset($doctor->display_name) && is_string($doctor->display_name) && $doctor->display_name !== '') {
            return $doctor->display_name;
        }

        if (isset($doctor->name) && is_string($doctor->name) && $doctor->name !== '') {
            return $doctor->name;
        }

        return 'Doctor';
    }
}
