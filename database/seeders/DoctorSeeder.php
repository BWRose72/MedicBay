<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use App\Models\Specialisation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            [
                'name' => 'Georgi Nikolov',
                'email' => 'georgi.nikolov@medicbay.bg',
                'password' => 'gn1234',
                'specialisation' => 'Cardiology',
                'phone' => '+359 2 987 1101',
                'bio' => 'Cardiologist with experience in preventive cardiology, hypertension care, and follow-up for chronic heart conditions.',
                'schedule' => [
                    ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '13:00'],
                    ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '13:00'],
                    ['day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '14:00'],
                    ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '13:00'],
                    ['day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '12:30'],
                ],
            ],
            [
                'name' => 'Elena Stoyanova',
                'email' => 'elena.stoyanova@medicbay.bg',
                'password' => 'es1234',
                'specialisation' => 'Paediatrics',
                'phone' => '+359 2 987 1102',
                'bio' => 'Paediatrician focused on child development, routine examinations, vaccination consultations, and acute paediatric care.',
                'schedule' => [
                    ['day_of_week' => 1, 'start_time' => '08:30', 'end_time' => '12:30'],
                    ['day_of_week' => 2, 'start_time' => '13:00', 'end_time' => '17:00'],
                    ['day_of_week' => 3, 'start_time' => '08:30', 'end_time' => '12:30'],
                    ['day_of_week' => 4, 'start_time' => '13:00', 'end_time' => '17:00'],
                    ['day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '13:00'],
                ],
            ],
            [
                'name' => 'Petar Dimitrov',
                'email' => 'petar.dimitrov@medicbay.bg',
                'password' => 'pd1234',
                'specialisation' => 'Neurology',
                'phone' => '+359 2 987 1103',
                'bio' => 'Neurologist treating headaches, vertigo, neuropathies, stroke follow-up, and long-term neurological conditions.',
                'schedule' => [
                    ['day_of_week' => 1, 'start_time' => '13:00', 'end_time' => '17:00'],
                    ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '13:00'],
                    ['day_of_week' => 3, 'start_time' => '13:00', 'end_time' => '17:00'],
                    ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '13:00'],
                    ['day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '14:00'],
                ],
            ],
        ];

        foreach ($doctors as $doctorData) {
            $specialisationId = Specialisation::query()
                ->where('name', $doctorData['specialisation'])
                ->value('specialisation_id');

            if (! $specialisationId) {
                throw new RuntimeException("Missing specialisation: {$doctorData['specialisation']}");
            }

            $user = User::withTrashed()->updateOrCreate(
                ['email' => $doctorData['email']],
                [
                    'name' => $doctorData['name'],
                    'password' => Hash::make($doctorData['password']),
                    'email_verified_at' => now(),
                ],
            );

            if ($user->trashed()) {
                $user->restore();
            }

            $user->forceFill(['email_verified_at' => now()])->save();

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles(['doctor']);
            }

            DB::transaction(function () use ($doctorData, $specialisationId, $user) {
                $doctor = Doctor::withTrashed()->updateOrCreate(
                    ['user_id' => (int) $user->getKey()],
                    [
                        'name' => $doctorData['name'],
                        'specialisation_id' => (int) $specialisationId,
                        'phone' => $doctorData['phone'],
                        'bio' => $doctorData['bio'],
                    ],
                );

                if ($doctor->trashed()) {
                    $doctor->restore();
                }

                DoctorWorkingHour::query()
                    ->where('doctor_id', (int) $doctor->doctor_id)
                    ->delete();

                foreach ($doctorData['schedule'] as $schedule) {
                    DoctorWorkingHour::query()->create([
                        'doctor_id' => (int) $doctor->doctor_id,
                        'day_of_week' => (int) $schedule['day_of_week'],
                        'start_time' => $schedule['start_time'],
                        'end_time' => $schedule['end_time'],
                        'effective_from' => '00:00',
                        'effective_to' => null,
                    ]);
                }
            });
        }
    }
}
