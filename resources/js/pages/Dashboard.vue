<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

defineOptions({ layout: AppPageLayout });

type PatientAppointmentRow = {
    appointment_id: number;
    doctor_id: number;
    doctor_name: string;
    start_time: string; // "Y-m-d H:i"
    status: string;
    has_left_review: boolean;
    can_review: boolean;
};

type DoctorAppointmentBox = {
    appointment_id: number;
    start_time: string; // "Y-m-d H:i"
    time: string; // "H:i"
    ends_at: string; // "Y-m-d H:i"
    patient_name: string;
    patient_gender: string;
    patient_age: number | null | undefined;
};

type PatientPayload = {
    past: PatientAppointmentRow[];
    this_week: PatientAppointmentRow[];
    future: PatientAppointmentRow[];
};

type DoctorPayload = {
    past: DoctorAppointmentBox[];
    current: DoctorAppointmentBox | null;
    future: DoctorAppointmentBox[];
};

type Props =
    | { dashboard_type: 'patient'; appointments?: PatientPayload }
    | { dashboard_type: 'doctor'; appointments?: DoctorPayload }
    | { dashboard_type: 'admin' | 'default'; appointments?: undefined };

const props = defineProps<Props>();

function genderAgeLine(a: DoctorAppointmentBox): string {
    const g = (a.patient_gender || '').trim();
    const age = a.patient_age ?? null;

    if (g && age !== null) return `${g}, ${age}`;
    if (g) return g;
    if (age !== null) return `${age}`;
    return '';
}

function markCompleted(id: number) {
    router.patch(`/appointments/${id}/status`, { status: 'Completed' }, { preserveScroll: true });
}

function markNoShow(id: number) {
    router.patch(`/appointments/${id}/status`, { status: 'NoShow' }, { preserveScroll: true });
}

function cancelAppointment(id: number) {
    router.patch(`/appointments/${id}/cancel`, {}, { preserveScroll: true });
}
</script>

<template>

    <Head title="Dashboard" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                        Dashboard
                    </h1>
                    <p class="mt-2 text-base text-muted-foreground">
                        <span v-if="props.dashboard_type === 'patient'">Your appointments</span>
                        <span v-else-if="props.dashboard_type === 'doctor'">Today’s appointments</span>
                        <span v-else>Overview</span>
                    </p>
                </div>

                <!-- Doctor dashboard -->
                <div v-if="props.dashboard_type === 'doctor'" class="mt-10 space-y-10">
                    <div>
                        <div class="mb-3 text-lg font-semibold text-foreground">Current</div>

                        <div v-if="props.appointments?.current"
                            class="flex items-stretch overflow-hidden rounded-2xl bg-primary/50 border border-border">
                            <div class="flex w-28 items-center justify-center border-r border-border px-3 py-5">
                                <div class="text-xl font-semibold text-foreground">{{ props.appointments.current.time }}
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col justify-center px-5 py-5">
                                <div class="text-base font-semibold text-foreground">{{
                                    props.appointments.current.patient_name }}</div>
                                <div class="mt-1 text-sm text-foreground/80">
                                    {{ genderAgeLine(props.appointments.current) }}
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 text-muted-foreground">
                            No current appointment.
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 text-lg font-semibold text-foreground">Past</div>

                        <div v-if="props.appointments?.past?.length" class="space-y-3">
                            <div v-for="a in props.appointments.past" :key="a.appointment_id"
                                class="flex items-stretch overflow-hidden rounded-2xl bg-primary/50 border border-border">
                                <div
                                    class="flex w-28 flex-col items-center justify-center gap-2 border-r border-border px-3 py-5">
                                    <div class="text-xl font-semibold text-foreground">{{ a.time }}</div>

                                    <div class="flex flex-col gap-2">
                                        <button type="button"
                                            class="rounded-md border border-border bg-background/50 px-2 py-1 text-xs font-semibold text-foreground hover:bg-muted"
                                            @click="markCompleted(a.appointment_id)">
                                            Completed
                                        </button>
                                        <button type="button"
                                            class="rounded-md border border-border bg-background/50 px-2 py-1 text-xs font-semibold text-foreground hover:bg-muted"
                                            @click="markNoShow(a.appointment_id)">
                                            NoShow
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col justify-center px-5 py-5">
                                    <div class="text-base font-semibold text-foreground">{{ a.patient_name }}</div>
                                    <div class="mt-1 text-sm text-foreground/80">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 text-muted-foreground">
                            No past appointments today.
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 text-lg font-semibold text-foreground">Future</div>

                        <div v-if="props.appointments?.future?.length" class="space-y-3">
                            <div v-for="a in props.appointments.future" :key="a.appointment_id"
                                class="flex items-stretch overflow-hidden rounded-2xl bg-primary/50 border border-border">
                                <div
                                    class="flex w-28 flex-col items-center justify-center gap-2 border-r border-border px-3 py-5">
                                    <div class="text-xl font-semibold text-foreground">{{ a.time }}</div>

                                    <button type="button"
                                        class="rounded-md border border-border bg-background/50 px-2 py-1 text-xs font-semibold text-foreground hover:bg-muted"
                                        @click="cancelAppointment(a.appointment_id)">
                                        Cancel
                                    </button>
                                </div>

                                <div class="flex flex-1 flex-col justify-center px-5 py-5">
                                    <div class="text-base font-semibold text-foreground">{{ a.patient_name }}</div>
                                    <div class="mt-1 text-sm text-foreground/80">
                                        {{ genderAgeLine(a) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else
                            class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6 text-muted-foreground">
                            No future appointments today.
                        </div>
                    </div>
                </div>

                <!-- Patient dashboard -->
                <div v-else-if="props.dashboard_type === 'patient'" class="mt-10 space-y-8">
                    <details class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border">
                        <summary class="cursor-pointer select-none px-6 py-4 text-lg font-semibold text-foreground">
                            Past appointments
                            <span class="ml-2 text-sm text-muted-foreground">
                                ({{ props.appointments?.past.length ?? 0 }})
                            </span>
                        </summary>

                        <div class="px-6 pb-6 space-y-4">
                            <div v-if="!props.appointments?.past.length" class="text-muted-foreground">
                                No past appointments.
                            </div>

                            <div v-for="a in props.appointments?.past" :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button type="button" class="rounded-md px-4 py-2 text-sm font-semibold"
                                    :class="a.can_review ? 'bg-secondary text-secondary-foreground hover:opacity-90' : 'bg-muted text-muted-foreground cursor-not-allowed'"
                                    :disabled="!a.can_review">
                                    Review
                                </button>
                            </div>
                        </div>
                    </details>

                    <div class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-6">
                        <div class="text-lg font-semibold text-foreground">
                            This week
                            <span class="ml-2 text-sm text-muted-foreground">
                                ({{ props.appointments?.this_week.length ?? 0 }})
                            </span>
                        </div>

                        <div class="mt-5 space-y-4">
                            <div v-if="!props.appointments?.this_week.length" class="text-muted-foreground">
                                No appointments this week.
                            </div>

                            <div v-for="a in props.appointments?.this_week" :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button type="button" class="rounded-md px-4 py-2 text-sm font-semibold"
                                    :class="a.can_review ? 'bg-secondary text-secondary-foreground hover:opacity-90' : 'bg-muted text-muted-foreground cursor-not-allowed'"
                                    :disabled="!a.can_review">
                                    Review
                                </button>
                            </div>
                        </div>
                    </div>

                    <details class="rounded-2xl bg-card/70 backdrop-blur-sm border border-border">
                        <summary class="cursor-pointer select-none px-6 py-4 text-lg font-semibold text-foreground">
                            Future appointments
                            <span class="ml-2 text-sm text-muted-foreground">
                                ({{ props.appointments?.future.length ?? 0 }})
                            </span>
                        </summary>

                        <div class="px-6 pb-6 space-y-4">
                            <div v-if="!props.appointments?.future.length" class="text-muted-foreground">
                                No future appointments.
                            </div>

                            <div v-for="a in props.appointments?.future" :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4 border border-border">
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button type="button"
                                    class="rounded-md px-4 py-2 text-sm font-semibold bg-muted text-muted-foreground cursor-not-allowed"
                                    disabled>
                                    Review
                                </button>
                            </div>
                        </div>
                    </details>
                </div>

                <!-- Default/admin placeholder -->
                <div v-else class="mt-10 rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-8">
                    <div class="text-xl font-semibold text-foreground">Dashboard</div>
                    <div class="mt-2 text-muted-foreground">
                        Role-specific dashboards for admin will be implemented later.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>