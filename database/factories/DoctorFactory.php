<?php

namespace Database\Factories;

use App\Models\Specialisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    private const SPECIALISATIONS = [
        'Cardiology',
        'Dermatology',
        'Endocrinology',
        'Gastroenterology',
        'Neurology',
        'Oncology',
        'Orthopaedics',
        'Paediatrics',
        'Psychiatry',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'specialisation_id' => Specialisation::query()->firstOrCreate([
                'name' => fake()->randomElement(self::SPECIALISATIONS),
            ])->getKey(),
            'phone' => fake()->phoneNumber(),
            'bio' => fake()->sentence(5),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($doctor) {
            if (Role::query()->where('name', 'doctor')->where('guard_name', 'web')->exists()) {
                $doctor->user->assignRole('doctor');
            }
        });
    }
}
