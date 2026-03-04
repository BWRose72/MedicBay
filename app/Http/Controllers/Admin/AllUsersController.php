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
    public function index(Request $request): Response
    {
        $actor = $request->user();

        if (!$actor || !method_exists($actor, 'hasRole') || !$actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->whereNull('deleted_at')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
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

    public function makeDoctor(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (!$actor || !method_exists($actor, 'hasRole') || !$actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'specialisation_id' => ['required', 'integer', 'exists:specialisations,specialisation_id'],
            'phone' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            // Remove any existing doctor profile (should not exist if patient, but safe)
            Doctor::query()->where('user_id', (int) $user->getKey())->delete();

            // Create doctor profile (required fields per migration)
            Doctor::query()->create([
                'user_id' => (int) $user->getKey(),
                'specialisation_id' => (int) $validated['specialisation_id'],
                'name' => (string) $validated['name'],
                'phone' => (string) $validated['phone'],
                'bio' => $validated['bio'] ?? null,
            ]);

            // Role: doctor only
            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles(['doctor']);
            }
        });

        return back();
    }

    public function fire(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (!$actor || !method_exists($actor, 'hasRole') || !$actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($user) {
            // Soft-delete doctor profile if exists
            Doctor::query()->where('user_id', (int) $user->getKey())->delete();

            // Role: patient only
            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles(['patient']);
            }
        });

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (!$actor || !method_exists($actor, 'hasRole') || !$actor->hasRole('admin')) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($user) {
            // Soft-delete related profiles so they don’t remain “active” records.
            Doctor::query()->where('user_id', (int) $user->getKey())->delete();
            Patient::query()->where('user_id', (int) $user->getKey())->delete();

            // Soft-delete user (your User model uses SoftDeletes)
            $user->delete();
        });

        return back();
    }
}