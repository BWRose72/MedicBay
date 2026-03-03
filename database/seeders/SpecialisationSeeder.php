<?php

namespace Database\Seeders;

use App\Models\Specialisation;
use Illuminate\Database\Seeder;

class SpecialisationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialisations = [
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

        foreach ($specialisations as $name) {
            Specialisation::firstOrCreate([
                'name' => $name,
            ]);
        }
    }
}
