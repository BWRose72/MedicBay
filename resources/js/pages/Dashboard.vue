<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

import AdminDashboard from '@/pages/AdminDashboard.vue';
import DoctorDashboard from '@/pages/DoctorDashboard.vue';
import PatientDashboard from '@/pages/PatientDashboard.vue';

defineOptions({ layout: AppPageLayout });

type DashboardType = 'admin' | 'doctor' | 'patient' | 'default';

type DoctorAppointmentBox = {
    appointment_id: number;
    start_time: string;
    time: string;
    ends_at: string;
    status?: string;
    patient_name: string;
    patient_gender: string;
    patient_age: number | null | undefined;
};

type DoctorPayload = {
    current: DoctorAppointmentBox | null;
    past: DoctorAppointmentBox[];
    future: DoctorAppointmentBox[];
};

type PatientAppointmentRow = {
    appointment_id: number;
    doctor_id: number;
    doctor_name: string;
    start_time: string;
    status: string;
    has_left_review: boolean;
    can_review: boolean;
    can_cancel: boolean;
    review_attitude: number | null;
    review_professionalism: number | null;
};

type PatientPayload = {
    current: PatientAppointmentRow | null;
    past: PatientAppointmentRow[];
    future: PatientAppointmentRow[];
};

type AdminStats = {
    users?: number;
    doctors?: number;
    patients?: number;
    appointments_today?: number;
};

type StatTile = {
    label: string;
    value: string | number;
    detail: string;
};

const props = defineProps<{
    dashboard_type: DashboardType;
    appointments?: DoctorPayload | PatientPayload;
    dashboard_stats?: AdminStats;
    notice?: string;
}>();

const emptyDoctorPayload = (): DoctorPayload => ({
    current: null,
    past: [],
    future: [],
});

const emptyPatientPayload = (): PatientPayload => ({
    current: null,
    past: [],
    future: [],
});

const roleLabel = computed(() => {
    if (props.dashboard_type === 'admin') return 'Admin';
    if (props.dashboard_type === 'doctor') return 'Doctor';
    if (props.dashboard_type === 'patient') return 'Patient';
    return 'Unavailable';
});

const subtitle = computed(() => {
    if (props.dashboard_type === 'admin') return 'Operational overview';
    if (props.dashboard_type === 'doctor')
        return "Today's appointments and follow-up work";
    if (props.dashboard_type === 'patient')
        return 'Appointments, cancellations, and reviews';
    return 'No dashboard is configured for this account';
});

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('en', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date()),
);

const doctorAppointments = computed<DoctorPayload>(() =>
    props.dashboard_type === 'doctor'
        ? ((props.appointments as DoctorPayload | undefined) ??
          emptyDoctorPayload())
        : emptyDoctorPayload(),
);

const patientAppointments = computed<PatientPayload>(() =>
    props.dashboard_type === 'patient'
        ? ((props.appointments as PatientPayload | undefined) ??
          emptyPatientPayload())
        : emptyPatientPayload(),
);

const stats = computed<StatTile[]>(() => {
    if (props.dashboard_type === 'admin') {
        return [
            {
                label: 'Users',
                value: props.dashboard_stats?.users ?? 0,
                detail: 'Active accounts',
            },
            {
                label: 'Doctors',
                value: props.dashboard_stats?.doctors ?? 0,
                detail: 'Active profiles',
            },
            {
                label: 'Patients',
                value: props.dashboard_stats?.patients ?? 0,
                detail: 'Active profiles',
            },
            {
                label: 'Today',
                value: props.dashboard_stats?.appointments_today ?? 0,
                detail: 'Appointments',
            },
        ];
    }

    if (props.dashboard_type === 'doctor') {
        const pending = doctorAppointments.value.past.filter(
            (appointment) =>
                (appointment.status ?? 'scheduled') === 'scheduled',
        ).length;

        return [
            {
                label: 'Current',
                value: doctorAppointments.value.current ? 'Active' : 'None',
                detail: 'Appointment now',
            },
            {
                label: 'Remaining',
                value: doctorAppointments.value.future.length,
                detail: 'Later today',
            },
            {
                label: 'Completed',
                value: doctorAppointments.value.past.filter(
                    (appointment) => appointment.status === 'completed',
                ).length,
                detail: 'Today',
            },
            { label: 'Pending', value: pending, detail: 'Status updates' },
        ];
    }

    if (props.dashboard_type === 'patient') {
        const reviewsAvailable = patientAppointments.value.past.filter(
            (appointment) => appointment.can_review,
        ).length;

        return [
            {
                label: 'Current',
                value: patientAppointments.value.current ? 'Active' : 'None',
                detail: 'Appointment now',
            },
            {
                label: 'Upcoming',
                value: patientAppointments.value.future.length,
                detail: 'Scheduled',
            },
            {
                label: 'Past',
                value: patientAppointments.value.past.length,
                detail: 'History',
            },
            { label: 'Reviews', value: reviewsAvailable, detail: 'Available' },
        ];
    }

    return [
        { label: 'Status', value: '-', detail: 'Unavailable' },
        { label: 'Role', value: '-', detail: 'Not configured' },
        { label: 'Actions', value: '-', detail: 'None' },
        { label: 'Today', value: '-', detail: todayLabel.value },
    ];
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="content-wrap">
        <div class="content-bg"></div>
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <header
                    class="flex flex-col gap-4 border-b border-border pb-6 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1
                                class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl"
                            >
                                Dashboard
                            </h1>
                            <span
                                class="rounded-md border border-border bg-card/70 px-3 py-1 text-xs font-semibold text-foreground"
                            >
                                {{ roleLabel }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ subtitle }}
                        </p>
                    </div>

                    <div class="text-sm font-medium text-muted-foreground">
                        {{ todayLabel }}
                    </div>
                </header>

                <div
                    v-if="props.notice"
                    class="mt-6 rounded-md border border-border bg-card/80 p-4 text-sm text-foreground"
                >
                    {{ props.notice }}
                </div>

                <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="rounded-lg border border-border bg-card/70 p-4 backdrop-blur-sm"
                    >
                        <div
                            class="text-xs font-semibold text-muted-foreground uppercase"
                        >
                            {{ stat.label }}
                        </div>
                        <div
                            class="mt-2 text-2xl font-semibold text-foreground"
                        >
                            {{ stat.value }}
                        </div>
                        <div class="mt-1 text-sm text-muted-foreground">
                            {{ stat.detail }}
                        </div>
                    </div>
                </section>

                <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem]">
                    <main class="min-w-0">
                        <AdminDashboard
                            v-if="props.dashboard_type === 'admin'"
                        />
                        <DoctorDashboard
                            v-else-if="props.dashboard_type === 'doctor'"
                            :appointments="doctorAppointments"
                        />
                        <PatientDashboard
                            v-else-if="props.dashboard_type === 'patient'"
                            :appointments="patientAppointments"
                        />

                        <div
                            v-else
                            class="rounded-lg border border-border bg-card/70 p-10 text-center"
                        >
                            <h2 class="text-xl font-semibold text-foreground">
                                Dashboard unavailable
                            </h2>
                            <p
                                class="mx-auto mt-2 max-w-md text-sm text-muted-foreground"
                            >
                                This account does not currently have a dashboard
                                role. You can return home or browse doctors.
                            </p>
                            <div class="mt-6 flex justify-center gap-3">
                                <Link
                                    href="/"
                                    class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90"
                                >
                                    Home
                                </Link>
                                <Link
                                    href="/doctors"
                                    class="rounded-md border border-border bg-background/70 px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                >
                                    Doctors
                                </Link>
                            </div>
                        </div>
                    </main>

                    <aside class="space-y-4">
                        <section
                            class="rounded-lg border border-border bg-card/70 p-4 backdrop-blur-sm"
                        >
                            <h2 class="text-sm font-semibold text-foreground">
                                Quick actions
                            </h2>
                            <div class="mt-4 grid gap-2">
                                <Link
                                    v-if="props.dashboard_type === 'doctor'"
                                    href="/doctors"
                                    class="rounded-md border border-border bg-background/70 px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                >
                                    View doctors
                                </Link>
                                <Link
                                    v-if="props.dashboard_type === 'patient'"
                                    href="/doctors"
                                    class="rounded-md border border-border bg-background/70 px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                >
                                    Book appointment
                                </Link>
                                <Link
                                    href="/settings/profile"
                                    class="rounded-md border border-border bg-background/70 px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted"
                                >
                                    Profile settings
                                </Link>
                            </div>
                        </section>

                        <section
                            class="rounded-lg border border-border bg-card/70 p-4 text-sm text-muted-foreground backdrop-blur-sm"
                        >
                            <h2 class="text-sm font-semibold text-foreground">
                                Alerts
                            </h2>
                            <p class="mt-3" v-if="props.notice">
                                {{ props.notice }}
                            </p>
                            <p class="mt-3" v-else>No new alerts.</p>
                        </section>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</template>
