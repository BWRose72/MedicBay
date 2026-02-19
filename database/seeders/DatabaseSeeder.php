<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialisation;
use App\Models\Patient;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(SpecialisationSeeder::class);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        $specialisationIds = Specialisation::pluck('specialisation_id');

        Doctor::factory()
            ->count(10)
            ->create([
                'specialisation_id' => fn () => $specialisationIds->random(),
        ]);

        Patient::factory()
            ->count(10)
            ->create();
        // User::factory(10)->create();
    }
}
