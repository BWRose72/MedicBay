<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });

type DoctorEditPayload = {
    doctor_id: number;
    display_name: string;
    name: string;
    phone: string;
    bio: string;
    photo_url: string;
    specialisation: { specialisation_id: number; name: string };
};

type ScheduleRow = {
    working_hours_id?: number | null;
    day_of_week: number;
    start_time: string;
    end_time: string;
};

type TimeOffRow = {
    time_off_id?: number | null;
    start_time: string;
    end_time: string;
};

const props = defineProps<{
    doctor: DoctorEditPayload;
    schedule: ScheduleRow[];
    time_offs: TimeOffRow[];
}>();

const form = useForm({
    name: props.doctor.name,
    phone: props.doctor.phone,
    bio: props.doctor.bio ?? '',
});

const scheduleForm = useForm({
    schedule: props.schedule.map((row) => ({ ...row })),
});

const timeOffForm = useForm({
    time_offs: props.time_offs.map((row) => ({ ...row })),
});

const photoForm = useForm<{
    photo: File | null;
}>({
    photo: null,
});

const weekDays = [
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
    { value: 7, label: 'Sunday' },
];

function submit() {
    form.patch(`/doctors/${props.doctor.doctor_id}`, {
        preserveScroll: true,
    });
}

function addScheduleRow() {
    scheduleForm.schedule.push({
        working_hours_id: null,
        day_of_week: 1,
        start_time: '09:00',
        end_time: '17:00',
    });
}

function removeScheduleRow(index: number) {
    scheduleForm.schedule.splice(index, 1);
}

function saveSchedule() {
    scheduleForm.patch(`/doctors/${props.doctor.doctor_id}/schedule`, {
        preserveScroll: true,
    });
}

function addTimeOffRow() {
    timeOffForm.time_offs.push({
        time_off_id: null,
        start_time: '12:00',
        end_time: '13:00',
    });
}

function removeTimeOffRow(index: number) {
    timeOffForm.time_offs.splice(index, 1);
}

function saveTimeOffs() {
    timeOffForm.patch(`/doctors/${props.doctor.doctor_id}/time-offs`, {
        preserveScroll: true,
    });
}

function onPhotoChange(event: Event) {
    const files = (event.target as HTMLInputElement).files;

    photoForm.photo = files?.[0] ?? null;
}

function savePhoto() {
    photoForm.post(`/doctors/${props.doctor.doctor_id}/photo`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => photoForm.reset(),
    });
}
</script>

<template>
    <Head title="Edit Doctor Profile" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                            Edit profile
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            {{ props.doctor.display_name }} · {{ props.doctor.specialisation?.name || '—' }}
                        </p>
                    </div>

                    <Link :href="`/doctors/${props.doctor.doctor_id}`" class="nav-link">
                        Back
                    </Link>
                </div>

                <form class="mt-10 max-w-2xl space-y-6" @submit.prevent="submit">
                    <div class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-foreground">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                            />
                            <div v-if="form.errors.name" class="mt-2 text-sm text-destructive">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-foreground">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                            />
                            <div v-if="form.errors.phone" class="mt-2 text-sm text-destructive">
                                {{ form.errors.phone }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-foreground">Bio</label>
                            <textarea
                                v-model="form.bio"
                                rows="7"
                                class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                            ></textarea>
                            <div v-if="form.errors.bio" class="mt-2 text-sm text-destructive">
                                {{ form.errors.bio }}
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                Save
                            </button>

                            <div v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">
                                Saved.
                            </div>
                        </div>
                    </div>
                </form>

                <form class="mt-10 max-w-2xl space-y-6" @submit.prevent="savePhoto">
                    <div class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 space-y-5">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                                Profile picture
                            </h2>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Upload a JPG, PNG, or WebP image. It will be saved as {{ props.doctor.doctor_id }}.jpg.
                            </p>
                        </div>

                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <img
                                :src="props.doctor.photo_url"
                                :alt="`Doctor ${props.doctor.name}`"
                                class="h-32 w-32 rounded-2xl object-cover"
                            />

                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-foreground">Image file</label>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="mt-2 block w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                                    @change="onPhotoChange"
                                />
                                <div v-if="photoForm.errors.photo" class="mt-2 text-sm text-destructive">
                                    {{ photoForm.errors.photo }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                                :disabled="photoForm.processing || !photoForm.photo"
                            >
                                Upload picture
                            </button>

                            <div v-if="photoForm.recentlySuccessful" class="text-sm text-muted-foreground">
                                Picture uploaded.
                            </div>
                        </div>
                    </div>
                </form>

                <form class="mt-10 space-y-6" @submit.prevent="saveSchedule">
                    <div class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                                    Weekly schedule
                                </h2>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    Add the doctor&apos;s working intervals. Appointment slots are generated every 30 minutes inside these times.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-md border border-border bg-background/70 px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                @click="addScheduleRow"
                            >
                                Add interval
                            </button>
                        </div>

                        <div v-if="scheduleForm.errors.schedule" class="mt-4 text-sm text-destructive">
                            {{ scheduleForm.errors.schedule }}
                        </div>

                        <div class="mt-6 space-y-4">
                            <div
                                v-for="(row, index) in scheduleForm.schedule"
                                :key="row.working_hours_id ?? `new-schedule-${index}`"
                                class="rounded-2xl bg-background/60 border border-border p-4"
                            >
                                <div class="grid gap-4 md:grid-cols-[1.2fr_1fr_1fr_auto] md:items-end">
                                    <div>
                                        <label class="block text-sm font-semibold text-foreground">Day</label>
                                        <select
                                            v-model.number="row.day_of_week"
                                            class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        >
                                            <option v-for="day in weekDays" :key="day.value" :value="day.value">
                                                {{ day.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-foreground">Start</label>
                                        <input
                                            v-model="row.start_time"
                                            type="time"
                                            step="1800"
                                            class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-foreground">End</label>
                                        <input
                                            v-model="row.end_time"
                                            type="time"
                                            step="1800"
                                            class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-md border border-border bg-background/70 px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                        @click="removeScheduleRow(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="scheduleForm.schedule.length === 0"
                                class="rounded-2xl bg-background/40 border border-border p-6 text-center text-sm text-muted-foreground"
                            >
                                No working intervals yet.
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                                :disabled="scheduleForm.processing"
                            >
                                Save schedule
                            </button>

                            <div v-if="scheduleForm.recentlySuccessful" class="text-sm text-muted-foreground">
                                Schedule saved.
                            </div>
                        </div>
                    </div>
                </form>

                <form class="mt-10 space-y-6" @submit.prevent="saveTimeOffs">
                    <div class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                                    Time-off intervals
                                </h2>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    Block recurring time ranges inside working hours, such as lunch breaks or unavailable periods.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-md border border-border bg-background/70 px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                @click="addTimeOffRow"
                            >
                                Add time off
                            </button>
                        </div>

                        <div v-if="timeOffForm.errors.time_offs" class="mt-4 text-sm text-destructive">
                            {{ timeOffForm.errors.time_offs }}
                        </div>

                        <div class="mt-6 space-y-4">
                            <div
                                v-for="(row, index) in timeOffForm.time_offs"
                                :key="row.time_off_id ?? `new-time-off-${index}`"
                                class="rounded-2xl bg-background/60 border border-border p-4"
                            >
                                <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                                    <div>
                                        <label class="block text-sm font-semibold text-foreground">Start</label>
                                        <input
                                            v-model="row.start_time"
                                            type="time"
                                            step="1800"
                                            class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-foreground">End</label>
                                        <input
                                            v-model="row.end_time"
                                            type="time"
                                            step="1800"
                                            class="mt-2 w-full rounded-md border border-border bg-background px-3 py-2 text-foreground"
                                        />
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-md border border-border bg-background/70 px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                        @click="removeTimeOffRow(index)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="timeOffForm.time_offs.length === 0"
                                class="rounded-2xl bg-background/40 border border-border p-6 text-center text-sm text-muted-foreground"
                            >
                                No time-off intervals yet.
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-60"
                                :disabled="timeOffForm.processing"
                            >
                                Save time off
                            </button>

                            <div v-if="timeOffForm.recentlySuccessful" class="text-sm text-muted-foreground">
                                Time off saved.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
