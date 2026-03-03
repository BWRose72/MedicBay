<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppPageLayout from '@/layouts/AppPageLayout.vue';

import { logout } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { edit as passwordEdit } from '@/routes/user-password';
import { edit as appearanceEdit } from '@/routes/appearance';
import { show as twoFactorShow } from '@/routes/two-factor';

defineOptions({ layout: AppPageLayout });

type AppointmentRow = {
    appointment_id: number;
    doctor_id: number;
    doctor_name: string;
    start_time: string; // "Y-m-d H:i"
    status: string;
    has_left_review: boolean;
    can_review: boolean;
};

const props = defineProps<{
    dashboard_type: 'patient' | 'default';
    appointments?: {
        past: AppointmentRow[];
        this_week: AppointmentRow[];
        future: AppointmentRow[];
    };
}>();

const page = usePage();
const user = page.props.auth?.user as { name?: string; email?: string } | undefined;

function initials(name?: string): string {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/).filter(Boolean);
    const a = parts[0]?.[0] ?? 'U';
    const b = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? '') : '';
    return (a + b).toUpperCase();
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="content-wrap">
        <div class="content-overlay"></div>

        <div class="content-foreground">
            <div class="container-main section-spacing">
                <!-- Title + Account dropdown -->
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-foreground">
                            Dashboard
                        </h1>
                        <p class="mt-2 text-base text-muted-foreground">
                            <span v-if="props.dashboard_type === 'patient'">Your appointments</span>
                            <span v-else>Overview</span>
                        </p>
                    </div>

                    <div class="relative">
                        <details class="group">
                            <summary
                                class="list-none cursor-pointer select-none rounded-md bg-card px-3 py-2 text-sm font-semibold text-foreground border border-border hover:bg-muted"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary/25 text-foreground font-semibold"
                                    >
                                        {{ initials(user?.name) }}
                                    </span>
                                    <span class="hidden sm:inline max-w-[180px] truncate">
                                        {{ user?.name ?? 'Account' }}
                                    </span>
                                    <span class="opacity-70">▾</span>
                                </span>
                            </summary>

                            <div class="absolute right-0 mt-2 w-60 rounded-xl bg-card shadow-lg border border-border p-2 z-50">
                                <div class="px-3 py-2">
                                    <div class="text-sm font-semibold text-foreground truncate">
                                        {{ user?.name ?? 'User' }}
                                    </div>
                                    <div class="text-xs text-muted-foreground truncate">
                                        {{ user?.email ?? '' }}
                                    </div>
                                </div>

                                <div class="my-2 h-px bg-border"></div>

                                <Link :href="profileEdit().url" class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Profile settings
                                </Link>
                                <Link :href="passwordEdit().url" class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Password settings
                                </Link>
                                <Link :href="twoFactorShow.url()" class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Two-factor authentication
                                </Link>
                                <Link :href="appearanceEdit().url" class="block rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Appearance settings
                                </Link>

                                <div class="my-2 h-px bg-border"></div>

                                <Link :href="logout()" as="button" class="w-full text-left rounded-md px-3 py-2 text-sm text-foreground hover:bg-muted">
                                    Sign out
                                </Link>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Patient dashboard -->
                <div v-if="props.dashboard_type === 'patient'" class="mt-10 space-y-8">
                    <!-- Past dropdown -->
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

                            <div
                                v-for="a in props.appointments?.past"
                                :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4"
                            >
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-md px-4 py-2 text-sm font-semibold"
                                    :class="a.can_review ? 'bg-secondary text-secondary-foreground hover:opacity-90' : 'bg-muted text-muted-foreground cursor-not-allowed'"
                                    :disabled="!a.can_review"
                                >
                                    Review
                                </button>
                            </div>
                        </div>
                    </details>

                    <!-- This week (always visible) -->
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

                            <div
                                v-for="a in props.appointments?.this_week"
                                :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4"
                            >
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-md px-4 py-2 text-sm font-semibold"
                                    :class="a.can_review ? 'bg-secondary text-secondary-foreground hover:opacity-90' : 'bg-muted text-muted-foreground cursor-not-allowed'"
                                    :disabled="!a.can_review"
                                >
                                    Review
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Future dropdown -->
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

                            <div
                                v-for="a in props.appointments?.future"
                                :key="a.appointment_id"
                                class="rounded-2xl bg-background/70 p-5 flex items-center justify-between gap-4"
                            >
                                <div>
                                    <div class="text-base font-semibold text-foreground">
                                        {{ a.start_time }} — {{ a.doctor_name }}
                                    </div>
                                    <div class="mt-1 text-sm text-muted-foreground">
                                        Status: {{ a.status }}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-md px-4 py-2 text-sm font-semibold bg-muted text-muted-foreground cursor-not-allowed"
                                    disabled
                                >
                                    Review
                                </button>
                            </div>
                        </div>
                    </details>
                </div>

                <!-- Default dashboard placeholder (doctor/admin later) -->
                <div v-else class="mt-10 rounded-2xl bg-card/70 backdrop-blur-sm border border-border p-8">
                    <div class="text-xl font-semibold text-foreground">Dashboard</div>
                    <div class="mt-2 text-muted-foreground">
                        Role-specific dashboards for admin/doctor will be implemented later.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>