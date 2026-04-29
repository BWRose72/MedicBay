<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $identifierColumn = Schema::hasColumn('patients', 'personal_identification_number')
            ? 'personal_identification_number'
            : 'medical_record_number';

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),

            // REQUIRED by your patients migration
            'gender' => ['required', 'string', 'max:50'],
            'personal_identification_number' => ['required', 'string', 'max:50', "unique:patients,{$identifierColumn}"],
            'date_of_birth' => ['required', 'date'],

            // OPTIONAL in your patients migration
            'phone' => ['nullable', 'string', 'max:50'],
        ])->validate();

        return DB::transaction(function () use ($input, $identifierColumn) {
            $user = User::create([
                'name' => (string) $input['name'],
                'email' => (string) $input['email'],
                'password' => Hash::make((string) $input['password']),
            ]);

            // Spatie: default role on registration
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('patient');
            }

            Patient::create([
                'user_id' => (int) $user->id,
                'gender' => (string) $input['gender'],
                $identifierColumn => (string) $input['personal_identification_number'],
                'date_of_birth' => (string) $input['date_of_birth'],
                'phone' => $input['phone'] ?? null,
            ]);

            return $user;
        });
    }
}
