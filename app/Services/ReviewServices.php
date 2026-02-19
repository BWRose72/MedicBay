<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReviewServices
{
    public function leaveReview(
        User $actor,
        int $appointmentId,
        int $attitude,
        int $professionalism
    ): Review {
        if (!$actor->can('review.leave')) {
            throw new AuthorizationException('Only patients can leave reviews.');
        }


        $this->assertRatingRange($attitude, 'attitude');
        $this->assertRatingRange($professionalism, 'professionalism');

        $patient = Patient::query()
            ->withoutTrashed()
            ->where('user_id', (int) $actor->getKey())
            ->firstOrFail();

        /** @var Appointment $appointment */
        $appointment = Appointment::query()
            ->with(['doctor', 'patient'])
            ->whereKey($appointmentId)
            ->firstOrFail();

        if ((int) $appointment->patient_id !== (int) $patient->patient_id) {
            throw new AuthorizationException('You can only review your own appointments.');
        }

        if ($appointment->status !== AppointmentStatus::Completed) {
            throw new InvalidArgumentException('Reviews can only be left for completed appointments.');
        }

        if ((bool) $appointment->has_left_review === true) {
            throw new InvalidArgumentException('A review has already been left for this appointment.');
        }

        $endsAt = CarbonImmutable::parse($appointment->ends_at);
        $now = CarbonImmutable::now();

        // Must be after the appointment ends
        if ($now->lessThan($endsAt)) {
            throw new InvalidArgumentException('You can only review after the appointment has ended.');
        }

        // Must be within 7 days after the appointment ends
        if ($now->greaterThan($endsAt->addWeek())) {
            throw new InvalidArgumentException('The review period (7 days) has expired.');
        }

        // Ensure doctor exists and is not soft-deleted (defensive)
        Doctor::query()
            ->withoutTrashed()
            ->whereKey((int) $appointment->doctor_id)
            ->firstOrFail();

        return DB::transaction(function () use ($appointment, $patient, $attitude, $professionalism) {
            // Lock the appointment row to avoid double-review races
            /** @var Appointment $locked */
            $locked = Appointment::query()
                ->whereKey((int) $appointment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((bool) $locked->has_left_review === true) {
                throw new InvalidArgumentException('A review has already been left for this appointment.');
            }

            if ($locked->status !== AppointmentStatus::Completed) {
                throw new InvalidArgumentException('Reviews can only be left for completed appointments.');
            }

            $review = Review::create([
                'patient_id'       => (int) $patient->patient_id,
                'doctor_id'        => (int) $locked->doctor_id,
                'attitude'         => $attitude,
                'professionalism'  => $professionalism,
            ]);

            $locked->has_left_review = true;
            $locked->save();

            return $review;
        });
    }

    public function adminDoctorReviews(User $actor, int $doctorId): Collection
    {
        if (!$actor->can('review.view')) {
            throw new AuthorizationException('Only admins can view individual reviews.');
        }

        Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        return Review::query()
            ->where('doctor_id', $doctorId)
            ->orderByDesc('review_id')
            ->get();
    }

    public function publicDoctorRatingSummary(int $doctorId): ?array
    {
        Doctor::query()
            ->withoutTrashed()
            ->whereKey($doctorId)
            ->firstOrFail();

        $count = Review::query()
            ->where('doctor_id', $doctorId)
            ->count();

        if ($count < 20) {
            return null;
        }

        $row = Review::query()
            ->where('doctor_id', $doctorId)
            ->selectRaw('AVG(attitude) as attitude_avg, AVG(professionalism) as professionalism_avg')
            ->first();

        $attitudeAvg = $row?->attitude_avg;
        $professionalismAvg = $row?->professionalism_avg;

        return [
            'doctor_id' => $doctorId,
            'reviews_count' => $count,
            'attitude_avg' => $attitudeAvg !== null ? (float) $attitudeAvg : null,
            'professionalism_avg' => $professionalismAvg !== null ? (float) $professionalismAvg : null,
        ];
    }

    private function assertRatingRange(int $value, string $field): void
    {
        if ($value < 0 || $value > 10) {
            throw new InvalidArgumentException("{$field} must be between 0 and 10.");
        }
    }
}
