<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorTimeOff;
use App\Models\DoctorWorkingHour;
use App\Services\DoctorServices;
use App\Services\DoctorScheduleService;
use App\Services\ReviewServices;
use App\Services\SpecialisationServices;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;


final class DoctorsController extends Controller
{
    public function index(
        Request $request,
        DoctorServices $doctorServices,
        ReviewServices $reviewServices,
        SpecialisationServices $specialisationServices,
    ): Response {
        $selectedSpecialisationId = $request->integer('specialisation_id') ?: null;

        $specialisations = $specialisationServices->all()
            ->map(fn ($s) => [
                'specialisation_id' => (int) $s->specialisation_id,
                'name' => (string) $s->name,
            ])
            ->values();

        $doctors = $selectedSpecialisationId
            ? $doctorServices->allBySpecialisationId($selectedSpecialisationId)
            : $doctorServices->all();

        $payload = $doctors->map(function ($d) use ($reviewServices, $specialisations) {
            $summary = $reviewServices->publicDoctorRatingSummary((int) $d->doctor_id);

            $spec = $specialisations->firstWhere('specialisation_id', (int) $d->specialisation_id);

            return [
                'doctor_id' => (int) $d->doctor_id,
                'name' => (string) $d->name,
                'specialisation_label' => $spec['name'] ?? null,
                'rating' => $summary === null ? null : [
                    'attitude_avg' => $summary['attitude_avg'],
                    'professionalism_avg' => $summary['professionalism_avg'],
                    'reviews_count' => $summary['reviews_count'],
                ],
            ];
        })->values();

        return Inertia::render('Doctors', [
            'doctors' => $payload,
            'specialisations' => $specialisations,
            'selectedSpecialisationId' => $selectedSpecialisationId,
        ]);
    }

    public function show(
        Request $request,
        int $doctor_id,
        DoctorServices $doctorServices,
        DoctorScheduleService $doctorScheduleService,
    ): Response {
        $doctor = $doctorServices->findOrFail($doctor_id);
        $doctor->load('specialisation');

        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $timezone = (string) config('app.timezone', 'Europe/Sofia');
        $selectedDate = $request->filled('date')
            ? CarbonImmutable::parse((string) $request->query('date'), $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->startOfDay();

        $slots = $doctorScheduleService->slotsForDate((int) $doctor->doctor_id, $selectedDate);

        $user = $request->user();

        $canEdit =
            $user !== null
            && method_exists($user, 'hasRole')
            && (
                $user->hasRole('admin')
                || ($user->hasRole('doctor') && (int) $doctor->user_id === (int) $user->getKey())
            );

        return Inertia::render('Doctors/Show', [
            'doctor' => [
                'doctor_id' => (int) $doctor->doctor_id,
                'user_id' => (int) $doctor->user_id,
                'name' => (string) $doctor->name,
                'display_name' => (string) $doctor->display_name,
                'specialisation' => [
                    'specialisation_id' => (int) ($doctor->specialisation?->specialisation_id ?? 0),
                    'name' => (string) ($doctor->specialisation?->name ?? ''),
                ],
                'phone' => (string) ($doctor->phone ?? ''),
                'bio' => (string) ($doctor->bio ?? ''),
            ],
            'can_edit' => $canEdit,
            'selected_date' => $selectedDate->format('Y-m-d'),
            'slots' => $slots,
        ]);
    }

    public function edit(
        Request $request,
        int $doctor_id,
        DoctorServices $doctorServices,
    ): Response {
        $doctor = $doctorServices->findOrFail($doctor_id);

        $this->authorizeDoctorEditor($request, $doctor);

        $doctor->load('specialisation');

        return Inertia::render('Doctors/Edit', [
            'doctor' => [
                'doctor_id' => (int) $doctor->doctor_id,
                'display_name' => (string) $doctor->display_name,
                'name' => (string) $doctor->name,
                'phone' => (string) $doctor->phone,
                'bio' => (string) ($doctor->bio ?? ''),
                'photo_url' => $this->doctorPhotoUrl((int) $doctor->doctor_id),
                'specialisation' => [
                    'specialisation_id' => (int) ($doctor->specialisation?->specialisation_id ?? 0),
                    'name' => (string) ($doctor->specialisation?->name ?? ''),
                ],
            ],
            'schedule' => DoctorWorkingHour::query()
                ->where('doctor_id', (int) $doctor->doctor_id)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->map(fn (DoctorWorkingHour $workingHour) => [
                    'working_hours_id' => (int) $workingHour->getKey(),
                    'day_of_week' => (int) $workingHour->day_of_week,
                    'start_time' => $this->formatTime($workingHour->start_time),
                    'end_time' => $this->formatTime($workingHour->end_time),
                ])
                ->values(),
            'time_offs' => DoctorTimeOff::query()
                ->where('doctor_id', (int) $doctor->doctor_id)
                ->orderBy('start_time')
                ->get()
                ->map(fn (DoctorTimeOff $timeOff) => [
                    'time_off_id' => (int) $timeOff->getKey(),
                    'start_time' => $this->formatTime($timeOff->start_time),
                    'end_time' => $this->formatTime($timeOff->end_time),
                ])
                ->values(),
        ]);
    }

    public function update(
        Request $request,
        int $doctor_id,
        DoctorServices $doctorServices,
    ): RedirectResponse {
        $doctor = $doctorServices->findOrFail($doctor_id);

        $this->authorizeDoctorEditor($request, $doctor);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string'],
        ]);

        $doctor->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'bio' => $validated['bio'] ?? null,
        ]);

        $doctor->save();

        return redirect()->route('doctors.show', ['doctor_id' => (int) $doctor->doctor_id]);
    }

    public function updateSchedule(Request $request, int $doctor_id, DoctorServices $doctorServices): RedirectResponse
    {
        $doctor = $doctorServices->findOrFail($doctor_id);

        $this->authorizeDoctorEditor($request, $doctor);

        $validated = $request->validate([
            'schedule' => ['array'],
            'schedule.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'schedule.*.start_time' => ['required', 'date_format:H:i'],
            'schedule.*.end_time' => ['required', 'date_format:H:i'],
        ]);

        $entries = $validated['schedule'] ?? [];

        foreach ($entries as $entry) {
            if ((string) $entry['end_time'] <= (string) $entry['start_time']) {
                return back()->withErrors([
                    'schedule' => 'Each schedule end time must be after its start time.',
                ]);
            }
        }

        DB::transaction(function () use ($doctor, $entries) {
            DoctorWorkingHour::query()
                ->where('doctor_id', (int) $doctor->doctor_id)
                ->delete();

            foreach ($entries as $entry) {
                DoctorWorkingHour::query()->create([
                    'doctor_id' => (int) $doctor->doctor_id,
                    'day_of_week' => (int) $entry['day_of_week'],
                    'start_time' => (string) $entry['start_time'],
                    'end_time' => (string) $entry['end_time'],
                    'effective_from' => '00:00',
                    'effective_to' => null,
                ]);
            }
        });

        return back();
    }

    public function updateTimeOffs(Request $request, int $doctor_id, DoctorServices $doctorServices): RedirectResponse
    {
        $doctor = $doctorServices->findOrFail($doctor_id);

        $this->authorizeDoctorEditor($request, $doctor);

        $validated = $request->validate([
            'time_offs' => ['array'],
            'time_offs.*.start_time' => ['required', 'date_format:H:i'],
            'time_offs.*.end_time' => ['required', 'date_format:H:i'],
        ]);

        $entries = $validated['time_offs'] ?? [];

        foreach ($entries as $entry) {
            if ((string) $entry['end_time'] <= (string) $entry['start_time']) {
                return back()->withErrors([
                    'time_offs' => 'Each time-off end time must be after its start time.',
                ]);
            }
        }

        DB::transaction(function () use ($doctor, $entries) {
            DoctorTimeOff::query()
                ->where('doctor_id', (int) $doctor->doctor_id)
                ->delete();

            foreach ($entries as $entry) {
                DoctorTimeOff::query()->create([
                    'doctor_id' => (int) $doctor->doctor_id,
                    'start_time' => (string) $entry['start_time'],
                    'end_time' => (string) $entry['end_time'],
                ]);
            }
        });

        return back();
    }

    public function updatePhoto(Request $request, int $doctor_id, DoctorServices $doctorServices): RedirectResponse
    {
        $doctor = $doctorServices->findOrFail($doctor_id);

        $this->authorizeDoctorEditor($request, $doctor);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        /** @var UploadedFile $photo */
        $photo = $validated['photo'];
        $targetDirectory = public_path('storage/doctors');

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $targetPath = $targetDirectory.DIRECTORY_SEPARATOR.$doctor->doctor_id.'.jpg';
        $this->saveUploadedDoctorPhotoAsJpeg($photo, $targetPath);

        return back();
    }

    public function indexAdmin() {}
    public function create() {}

    private function authorizeDoctorEditor(Request $request, Doctor $doctor): void
    {
        $user = $request->user();

        $allowed =
            $user !== null
            && method_exists($user, 'hasRole')
            && (
                $user->hasRole('admin')
                || ($user->hasRole('doctor') && (int) $doctor->user_id === (int) $user->getKey())
            );

        if (!$allowed) {
            abort(HttpResponse::HTTP_FORBIDDEN);
        }
    }

    private function formatTime(mixed $value): string
    {
        return CarbonImmutable::parse((string) $value, (string) config('app.timezone', 'Europe/Sofia'))->format('H:i');
    }

    private function doctorPhotoUrl(int $doctorId): string
    {
        $path = public_path("storage/doctors/{$doctorId}.jpg");
        $fileName = is_file($path) ? "{$doctorId}.jpg" : '0.jpg';
        $resolvedPath = public_path("storage/doctors/{$fileName}");
        $version = is_file($resolvedPath) ? filemtime($resolvedPath) : time();

        return "/storage/doctors/{$fileName}?v={$version}";
    }

    private function saveUploadedDoctorPhotoAsJpeg(UploadedFile $photo, string $targetPath): void
    {
        $sourcePath = $photo->getRealPath();

        if ($sourcePath === false) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'The uploaded image could not be read.');
        }

        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'The uploaded file is not a valid image.');
        }

        if ($imageInfo[2] === IMAGETYPE_JPEG) {
            $photo->move(dirname($targetPath), basename($targetPath));

            return;
        }

        if (! function_exists('imagecreatetruecolor')) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'PNG and WebP conversion requires the PHP GD extension. Upload a JPG image or enable GD.');
        }

        $sourceImage = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if ($sourceImage === false) {
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'The uploaded image type is not supported.');
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            imagedestroy($sourceImage);
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'The uploaded image could not be processed.');
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $sourceImage, 0, 0, 0, 0, $width, $height);

        if (! imagejpeg($canvas, $targetPath, 90)) {
            imagedestroy($sourceImage);
            imagedestroy($canvas);
            abort(HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'The uploaded image could not be saved.');
        }

        imagedestroy($sourceImage);
        imagedestroy($canvas);
    }
}
