<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'gender'=> $this->faker->randomElement(['male', 'female']),
            'medical_record_number' => fake()->unique()->regexify('[A-Z0-9]{10}'),
            'date_of_birth' => fake()->date(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
