<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class PatientsController extends Controller
{
    /**
     * Show a patient profile to an admin or the patient owner.
     */
    public function show(Request $request, int $patient_id): Response
    {
        $patient = $this->findPatient($patient_id);

        $this->authorizePatientViewer($request, $patient);

        $patient->load('user');

        return Inertia::render('Patients/Show', [
            'patient' => $this->patientPayload($patient),
            'can_edit' => $this->canEditPatient($request, $patient),
        ]);
    }

    /**
     * Show the edit screen for an admin or the patient owner.
     */
    public function edit(Request $request, int $patient_id): Response
    {
        $patient = $this->findPatient($patient_id);

        $this->authorizePatientViewer($request, $patient);

        $patient->load('user');

        return Inertia::render('Patients/Edit', [
            'patient' => $this->patientPayload($patient),
        ]);
    }

    /**
     * Update a patient profile and linked user account fields.
     */
    public function update(Request $request, int $patient_id): RedirectResponse
    {
        $patient = $this->findPatient($patient_id);

        $this->authorizePatientViewer($request, $patient);

        $identifierColumn = $this->patientIdentifierColumn();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore((int) $patient->user_id),
            ],
            'gender' => ['required', 'string', 'max:50'],
            'personal_identification_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('patients', $identifierColumn)->ignore((int) $patient->getKey(), 'patient_id'),
            ],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $patient->user?->forceFill([
            'name' => (string) $validated['name'],
            'email' => (string) $validated['email'],
        ])->save();

        $patient->fill([
            'gender' => (string) $validated['gender'],
            'date_of_birth' => (string) $validated['date_of_birth'],
            'phone' => $validated['phone'] ?? null,
        ]);
        $patient->setAttribute($identifierColumn, (string) $validated['personal_identification_number']);
        $patient->save();

        return to_route('patients.show', ['patient_id' => (int) $patient->getKey()]);
    }

    /**
     * Find an active patient by id or fail.
     */
    private function findPatient(int $patientId): Patient
    {
        return Patient::query()
            ->withoutTrashed()
            ->with('user')
            ->whereKey($patientId)
            ->firstOrFail();
    }

    /**
     * Abort unless the request user may view or edit the patient profile.
     */
    private function authorizePatientViewer(Request $request, Patient $patient): void
    {
        if (! $this->canEditPatient($request, $patient)) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }
    }

    /**
     * Determine whether the request user may manage the patient profile.
     */
    private function canEditPatient(Request $request, Patient $patient): bool
    {
        $user = $request->user();

        return $user !== null
            && method_exists($user, 'hasRole')
            && (
                $user->hasRole('admin')
                || ($user->hasRole('patient') && (int) $patient->user_id === (int) $user->getKey())
            );
    }

    /**
     * Build the patient payload used by patient profile pages.
     *
     * @return array<string, mixed>
     */
    private function patientPayload(Patient $patient): array
    {
        $dateOfBirth = $patient->date_of_birth
            ? CarbonImmutable::parse($patient->date_of_birth)->format('Y-m-d')
            : null;

        return [
            'patient_id' => (int) $patient->patient_id,
            'user_id' => (int) $patient->user_id,
            'name' => (string) ($patient->user?->name ?? ''),
            'email' => (string) ($patient->user?->email ?? ''),
            'gender' => (string) ($patient->gender ?? ''),
            'personal_identification_number' => (string) ($patient->personal_identification_number ?? $patient->medical_record_number ?? ''),
            'date_of_birth' => $dateOfBirth,
            'age' => $patient->age,
            'phone' => (string) ($patient->phone ?? ''),
        ];
    }

    /**
     * Resolve the patient identifier column used by the active database schema.
     */
    private function patientIdentifierColumn(): string
    {
        return Schema::hasColumn('patients', 'personal_identification_number')
            ? 'personal_identification_number'
            : 'medical_record_number';
    }
}
