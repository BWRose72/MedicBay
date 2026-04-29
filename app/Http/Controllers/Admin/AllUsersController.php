<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialisation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class AllUsersController extends Controller
{
    /**
     * Render the admin user-management screen.
     */
    public function index(Request $request): Response
    {
        $actor = $request->user();

        if (! $actor || ! method_exists($actor, 'hasRole') || ! $actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->whereNull('deleted_at')
            ->whereDoesntHave('roles', function ($roleQuery) {
                $roleQuery
                    ->where('roles.name', 'admin')
                    ->where('roles.guard_name', 'web');
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->get();

        $patientPhones = Patient::query()
            ->withoutTrashed()
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('phone', 'user_id');

        $users = $users
            ->map(function (User $u) use ($patientPhones) {
                $isDoctor = method_exists($u, 'hasRole') ? $u->hasRole('doctor') : false;
                $isPatient = method_exists($u, 'hasRole') ? $u->hasRole('patient') : false;

                $doctorId = null;
                if ($isDoctor) {
                    $doctorId = Doctor::query()
                        ->withoutTrashed()
                        ->where('user_id', (int) $u->getKey())
                        ->value('doctor_id');
                }

                return [
                    'user_id' => (int) $u->getKey(),
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                    'type' => $isDoctor ? 'doctor' : ($isPatient ? 'patient' : 'unknown'),
                    'doctor_id' => $doctorId ? (int) $doctorId : null,
                    'phone' => $patientPhones->get($u->getKey()),
                ];
            })
            ->values();

        $specialisations = Specialisation::query()
            ->select(['specialisation_id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'specialisation_id' => (int) $s->specialisation_id,
                'name' => (string) $s->name,
            ])
            ->values();

        return Inertia::render('Admin/AllUsers', [
            'q' => $q,
            'users' => $users,
            'specialisations' => $specialisations,
        ]);
    }

    /**
     * Convert a non-admin user into a doctor and restore or create their doctor profile.
     */
    public function makeDoctor(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! method_exists($actor, 'hasRole') || ! $actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $this->abortIfAdminUser($user);

        $validated = $request->validate([
            'specialisation_id' => ['required', 'integer', 'exists:specialisations,specialisation_id'],
            'phone' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $user->forceFill([
                'name' => (string) $validated['name'],
            ])->save();

            $doctor = Doctor::withTrashed()
                ->where('user_id', (int) $user->getKey())
                ->first();

            if ($doctor) {
                $doctor->fill([
                    'specialisation_id' => (int) $validated['specialisation_id'],
                    'phone' => (string) $validated['phone'],
                    'bio' => $validated['bio'] ?? null,
                ]);
                $doctor->restore();
                $doctor->save();
            } else {
                Doctor::query()->create([
                    'user_id' => (int) $user->getKey(),
                    'specialisation_id' => (int) $validated['specialisation_id'],
                    'phone' => (string) $validated['phone'],
                    'bio' => $validated['bio'] ?? null,
                ]);
            }

            Patient::query()
                ->where('user_id', (int) $user->getKey())
                ->get()
                ->each
                ->delete();

            $this->promoteToDoctorRole($user);
        });

        return back();
    }

    /**
     * Remove a user's doctor profile and restore them to the patient role.
     */
    public function fire(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! method_exists($actor, 'hasRole') || ! $actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $this->abortIfAdminUser($user);

        DB::transaction(function () use ($user) {
            Doctor::query()
                ->where('user_id', (int) $user->getKey())
                ->get()
                ->each
                ->delete();

            Patient::withTrashed()
                ->where('user_id', (int) $user->getKey())
                ->restore();

            // Role: patient only
            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles(['patient']);
            }
        });

        return back();
    }

    /**
     * Soft-delete a non-admin user and any active patient or doctor profiles.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor || ! method_exists($actor, 'hasRole') || ! $actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $this->abortIfAdminUser($user);

        DB::transaction(function () use ($user) {
            // Soft-delete related profiles so they don’t remain “active” records.
            Doctor::query()
                ->where('user_id', (int) $user->getKey())
                ->get()
                ->each
                ->delete();

            Patient::query()
                ->where('user_id', (int) $user->getKey())
                ->get()
                ->each
                ->delete();

            // Soft-delete user (your User model uses SoftDeletes)
            $user->delete();
        });

        return back();
    }

    /**
     * Prevent admin accounts from being modified by user-management actions.
     */
    private function abortIfAdminUser(User $user): void
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }
    }

    /**
     * Assign the doctor role while removing the patient role when present.
     */
    private function promoteToDoctorRole(User $user): void
    {
        if (method_exists($user, 'hasRole') && method_exists($user, 'removeRole') && $user->hasRole('patient')) {
            $user->removeRole('patient');
        }

        if (method_exists($user, 'hasRole') && method_exists($user, 'assignRole') && ! $user->hasRole('doctor')) {
            $user->assignRole('doctor');
        }
    }
}
