<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialisation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin1234'),
        ]);

        $admin->assignRole('admin');

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
