<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(SpecialisationSeeder::class);

        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin1234'),
                'email_verified_at' => now(),
            ]
        );

        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole('admin');

        $this->call(PatientSeeder::class);
        $this->call(DoctorSeeder::class);
    }
}
