<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            [
                'name' => 'Ivan Ivanov',
                'email' => 'ivanivanov@example.com',
                'password' => 'ii1234',
                'gender' => 'male',
                'personal_identification_number' => '8603141234',
                'date_of_birth' => '1986-03-14',
                'phone' => '+359 888 123 101',
            ],
            [
                'name' => 'Maria Petrova',
                'email' => 'mariapetrova@example.com',
                'password' => 'mp1234',
                'gender' => 'female',
                'personal_identification_number' => '9207221234',
                'date_of_birth' => '1992-07-22',
                'phone' => '+359 887 456 202',
            ],
            [
                'name' => 'Dimitar Dimitrov',
                'email' => 'dimitardimitrov@example.com',
                'password' => 'dd1234',
                'gender' => 'male',
                'personal_identification_number' => '7811051234',
                'date_of_birth' => '1978-11-05',
                'phone' => '+359 886 789 303',
            ],
        ];

        foreach ($patients as $patientData) {
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $patientData['email']],
                [
                    'name' => $patientData['name'],
                    'password' => Hash::make($patientData['password']),
                    'email_verified_at' => now(),
                ],
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $user->forceFill(['email_verified_at' => now()])->save();

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles(['patient']);
            }

            $patient = Patient::withTrashed()->updateOrCreate(
                ['user_id' => (int) $user->getKey()],
                [
                    'gender' => $patientData['gender'],
                    'personal_identification_number' => $patientData['personal_identification_number'],
                    'date_of_birth' => $patientData['date_of_birth'],
                    'phone' => $patientData['phone'],
                ],
            );

            if ($patient->trashed()) {
                $patient->restore();
            }
        }
    }
}
