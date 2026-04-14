<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
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
        'name' => "placeholder",
        'phone' => fake()->phoneNumber(),
        'bio' => fake()->sentence(5),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($doctor) {
            $doctor->name = $doctor->user->name;
            $doctor->save();

            $doctor->user->assignRole('doctor');
        });
    }
}
