<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $doctor = Role::firstOrCreate(['name' => 'doctor']);
        $patient = Role::firstOrCreate(['name' => 'patient']);

        // Create permissions
        $permissions = [
            'patient',
            'patient.view',
            'doctor',
            'doctor.create',
            'doctor.update',
            'doctor.delete',
            'schedule.view',
            'schedule.book',
            'review.view',
            'review.leave',
            'admin_doctor',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions
        $admin->givePermissionTo($permissions);

        $patient->givePermissionTo([
            'patient',
            'patient.view',
            'schedule.view',
            'schedule.book',
            'review.leave',
        ]);

        $doctor->givePermissionTo([
            'doctor',
            'patient.view',
            'schedule.view',
            'admin_doctor',
        ]);
    }
}
