<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Specialisation;

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
