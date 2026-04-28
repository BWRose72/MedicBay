<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'start_time' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'has_left_review' => false,
            'status' => AppointmentStatus::Scheduled,
        ];
    }
}
